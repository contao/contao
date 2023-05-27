<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\InsertTag;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\InsertTags;
use Contao\StringUtil;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Parse and replace insert tags.
 *
 * Formal syntax (EBNF):
 *
 *        InsertTag ::= "{{" Name Parameter * Flag * "}}"
 *             Name ::= [a-z#x80-#xFF] [a-z0-9_#x80-#xFF] *
 *        Parameter ::= "::" ( KeyValuePair | Value )
 *             Flag ::= "|" [^{}|] *
 *     KeyValuePair ::= Key "=" Value
 *              Key ::= [^{}|=] *
 *            Value ::= ( [^{}|] | InsertTag ) *
 */
class InsertTagParser implements ResetInterface
{
    private const TAG_REGEX = /** @lang RegExp */ '
        (?<it>                 # Named capturing group "it"
            {{                 # Starts with two opening curly braces
            [a-z0-9\x80-\xFF]  # The first letter must not be a reserved character of Twig, Mustache or similar template engines (see #805)
            (?>[^{}]|(?&it))*  # Match any character not curly brace or a nested insert tag
            }}                 # Ends with two closing curly braces
        )';

    private const PARAMETER_REGEX = /** @lang RegExp */ '
        ::                        # Starts with double colon
        (?:
            [^{}|:]               # Match any character not curly brace, pipe or colon
            |:(?!:)               # Or a single colon (not followed by another colon)
            |'.self::TAG_REGEX.'  # Or an insert tag
        )*';

    /**
     * @var array<string,InsertTagSubscription>
     */
    private array $subscriptions = [];

    /**
     * @var array<string,InsertTagSubscription>
     */
    private array $blockSubscriptions = [];

    /**
     * @var array<string,\Closure(InsertTagFlag,InsertTagResult):InsertTagResult>
     */
    private array $flagCallbacks = [];

    public function __construct(private ContaoFramework $framework, private LoggerInterface $logger, private InsertTags|null $insertTags = null)
    {
    }

    public function addSubscription(InsertTagSubscription $subscription): void
    {
        unset($this->blockSubscriptions[$subscription->name]);
        $this->subscriptions[$subscription->name] = $subscription;
    }

    public function addBlockSubscription(InsertTagSubscription $subscription): void
    {
        unset($this->subscriptions[$subscription->name]);
        $this->blockSubscriptions[$subscription->name] = $subscription;
    }

    public function addFlagCallback(string $name, object $service, string $method): void
    {
        $this->flagCallbacks[$name] = $service->$method(...);
    }

    public function replace(ParsedSequence|string $input): string
    {
        // TODO:
        return $this->replaceInline($input);

        /*
        if(!$input instanceof ParsedSequence) {
            $input = $this->parse($input);
        }

        $return = '';

        foreach ($input as $item) {
            if (\is_string($item)) {
                $return .= $item;
            } else {
                try {
                    $return .= $this->renderSubscription($item)?->getValue() ?? $item->serialize();
                } catch (\Throwable) {
                    // TODO: Throw and catch specific exceptions that are caused by legacy insert tags
                    $return .= $item->serialize();
                }
            }
        }

        return (string) $this->callLegacyClass($return, true);
        */
    }

    public function replaceChunked(string $input): ChunkedText
    {
        return new ChunkedText([$this->replace($input)]);

        // return $this->callLegacyClass($input, true);
    }

    public function replaceInline(ParsedSequence|string $input): string
    {
        if (!$input instanceof ParsedSequence) {
            $input = $this->parse($input);
        }

        $input = $this->handleLegacyTagsHook($input, false);

        $return = '';
        $wrapStart = null;
        $wrapContent = [];

        foreach ($input as $item) {
            if (
                $wrapStart
                && $item instanceof InsertTag
                && $item->getName() === $this->blockSubscriptions[$wrapStart->getName()]->endTag
            ) {
                $return .= $this->replaceInline($this->renderBlockSubscription($wrapStart, new ParsedSequence($wrapContent)));

                $wrapStart = null;
                $wrapContent = [];

                // Reprocess non-empty end tags to enable chaining block insert tags
                // E.g. `{{iflng::de}}…{{iflng::en}}…{{iflng}}`
                if (!\count($item->getParameters()->all())) {
                    continue;
                }
            }

            if ($wrapStart) {
                $wrapContent[] = $item;

                continue;
            }

            if (\is_string($item)) {
                $return .= $item;
                continue;
            }

            if ($this->blockSubscriptions[$item->getName()] ?? false) {
                $wrapStart = $item;
                continue;
            }

            $return .= $this->renderSubscription($item)?->getValue() ?? $item->serialize();
        }

        // Missing end tag
        if ($wrapStart) {
            $return .= $wrapStart->serialize();
            $return .= $this->replaceInline(new ParsedSequence($wrapContent));
        }

        return $return;
    }

    public function replaceInlineChunked(string $input): ChunkedText
    {
        return new ChunkedText([$this->replaceInline($input)]);

        // return $this->callLegacyClass($input, false);
    }

    /**
     * @deprecated Deprecated since Contao 5.1 to be removed in Contao 6. Use renderTag() instead.
     */
    public function render(string $input): string
    {
        trigger_deprecation('contao/core-bundle', '5.1', '"%s()" is deprecated. use "%s::renderTag()" instead.', __METHOD__, __CLASS__);

        return $this->renderTag($input)->getValue();
    }

    public function renderTag(InsertTag|string $input): InsertTagResult
    {
        if ($input instanceof InsertTag) {
            $tag = $input;
        } else {
            try {
                $tag = $this->parseTag($input);
            } catch (\InvalidArgumentException $exception) {
                trigger_deprecation('contao/core-bundle', '5.0', $exception->getMessage().'. This will no longer work in Contao 6.0.');
                $tag = null;
            }
        }

        if (null !== $tag) {
            $result = $this->renderSubscription($tag);

            if (null !== $result) {
                return $result;
            }
        }

        // Fallback to old implementation
        if ($input instanceof InsertTag) {
            $input = $input->serialize();
        }

        $chunked = iterator_to_array($this->replaceInlineChunked('{{'.$input.'}}'));

        if (!$chunked) {
            return new InsertTagResult('', OutputType::text);
        }

        if (1 !== \count($chunked) || ChunkedText::TYPE_RAW !== $chunked[0][0] || !\is_string($chunked[0][1])) {
            throw new \RuntimeException('Rendering a single insert tag has to return a single raw chunk');
        }

        return new InsertTagResult($chunked[0][1], OutputType::html);
    }

    /**
     * @internal
     */
    public function renderFlagForLegacyResult(string $flag, string $result): string|false
    {
        if (!$callback = $this->flagCallbacks[strtolower($flag)] ?? null) {
            return false;
        }

        return $callback(
            new InsertTagFlag($flag),
            new InsertTagResult($result, OutputType::html),
        )->getValue();
    }

    public function parse(string $input): ParsedSequence
    {
        if (null === $this->insertTags) {
            $this->framework->initialize();
            $this->insertTags = new InsertTags();
        }

        $input = $this->insertTags->encodeHtmlAttributes($input);

        if (!preg_match_all('('.self::TAG_REGEX.')x', $input, $matches, PREG_OFFSET_CAPTURE)) {
            return new ParsedSequence([$input]);
        }

        $lastOffset = 0;
        $result = [];

        foreach ($matches[0] as [$insertTag, $offset]) {
            $result[] = substr($input, $lastOffset, $offset - $lastOffset);

            try {
                $result[] = $this->parseTag(substr($insertTag, 2, -2));
            } catch (\Throwable) {
                $result[] = $insertTag;
            }

            $lastOffset = $offset + \strlen((string) $insertTag);
        }

        $result[] = substr($input, $lastOffset);

        return new ParsedSequence($result);
    }

    public function parseTag(string $insertTag): InsertTag
    {
        $flags = [];

        if (preg_match('/\|[^{}]*$/', $insertTag, $flags)) {
            $insertTag = substr($insertTag, 0, \strlen($insertTag) - \strlen((string) $flags[0]));
            $flags = explode('|', substr($flags[0], 1));
        }

        $parameters = explode('::', $insertTag, 2);
        $name = array_shift($parameters);

        if (!preg_match('/^[a-z\x80-\xFF][a-z0-9_\x80-\xFF]*$/i', $name)) {
            throw new \InvalidArgumentException(sprintf('Invalid insert tag name "%s"', $name));
        }

        if ($parameters) {
            preg_match_all('('.self::PARAMETER_REGEX.'|.)xs', '::'.$parameters[0], $parameterMatches);

            foreach ($parameterMatches[0] ?? [''] as $index => $parameterMatch) {
                if (!str_starts_with($parameterMatch, '::')) {
                    throw new \InvalidArgumentException(sprintf('Invalid insert tag parameter syntax "%s"', $parameters[0]));
                }
                $parameterMatches[0][$index] = substr($parameterMatch, 2);
            }

            /** @var list<ParsedSequence> $parameters */
            $parameters = array_map($this->parse(...), $parameterMatches[0]);
        }

        if (strtolower($name) !== $name) {
            trigger_deprecation('contao/core-bundle', '5.0', 'Insert tags with uppercase letters ("%s") have been deprecated and will no longer work in Contao 6.0.', $name);
            $name = strtolower($name);
        }

        foreach ($flags as $flag) {
            if (strtolower($flag) !== $flag) {
                trigger_deprecation('contao/core-bundle', '5.0', 'Insert tag flags with uppercase letters ("%s") have been deprecated and will no longer work in Contao 6.0.', $flag);
            }
        }

        $tag = new ParsedInsertTag(
            $name,
            new ParsedParameters($parameters),
            array_map(static fn ($flag) => new InsertTagFlag($flag), $flags)
        );

        if ($tag->getParameters()->hasInsertTags()) {
            return $tag;
        }

        return $this->resolveNestedTags($tag);
    }

    public function reset(): void
    {
        InsertTags::reset();
    }

    /**
     * @internal
     */
    public function hasInsertTag(string $name): bool
    {
        return
            isset($this->subscriptions[$name])
            || isset($this->blockSubscriptions[$name]);
    }

    private function renderSubscription(InsertTag $tag): InsertTagResult|null
    {
        if (!$subscription = $this->subscriptions[$tag->getName()] ?? null) {
            return null;
        }

        if ($subscription->resolveNestedTags) {
            $tag = $this->resolveNestedTags($tag);
        } else {
            $tag = $this->unresolveTag($tag);
        }

        $result = $subscription->service->{$subscription->method}($tag);

        foreach ($tag->getFlags() as $flag) {
            if ($callback = $this->flagCallbacks[strtolower($flag->getName())] ?? null) {
                $result = $callback($flag, $result);
            } else {
                $result = $this->handleLegacyFlagsHook($result, $flag, $tag);
            }
        }

        return $result;
    }

    private function renderBlockSubscription(InsertTag $tag, ParsedSequence|null $content = null): ParsedSequence|null
    {
        if (!$subscription = $this->blockSubscriptions[$tag->getName()] ?? null) {
            return null;
        }

        if ($subscription->resolveNestedTags) {
            $tag = $this->resolveNestedTags($tag);
        } else {
            $tag = $this->unresolveTag($tag);
        }

        return $subscription->service->{$subscription->method}($tag, $content);
    }

    private function resolveNestedTags(InsertTag $tag): ResolvedInsertTag
    {
        if ($tag instanceof ResolvedInsertTag) {
            return $tag;
        }

        if ($tag instanceof ParsedInsertTag) {
            return new ResolvedInsertTag(
                $tag->getName(),
                $this->resolveParameters($tag->getParameters()),
                $tag->getFlags(),
            );
        }

        throw new \InvalidArgumentException(sprintf('Unsupported insert tag class "%s"', $tag::class));
    }

    private function unresolveTag(InsertTag $tag): ParsedInsertTag
    {
        if ($tag instanceof ParsedInsertTag) {
            return $tag;
        }

        if ($tag instanceof ResolvedInsertTag) {
            return new ParsedInsertTag(
                $tag->getName(),
                new ParsedParameters(array_map(static fn ($param) => new ParsedSequence([(string) $param]), $tag->getParameters()->all())),
                $tag->getFlags(),
            );
        }

        throw new \InvalidArgumentException(sprintf('Unsupported insert tag class "%s"', $tag::class));
    }

    private function resolveParameters(ParsedParameters $parameters): ResolvedParameters
    {
        $resolvedParameters = [];

        foreach ($parameters->all() as $parameter) {
            $value = '';

            foreach ($parameter as $item) {
                if (\is_string($item)) {
                    $value .= $item;

                    continue;
                }

                $value .= $this->renderTag($value)->getValue();
            }

            $resolvedParameters[] = $value;
        }

        return new ResolvedParameters($resolvedParameters);
    }

    /**
     * @return array<string|null, array>
     */
    private function parseQuery(ParsedSequence $query): array
    {
        $nestedTags = [];

        $queryString = '';

        foreach ($query as $item) {
            if (!\is_string($item)) {
                /** @var non-empty-string $uuid */
                $uuid = Uuid::v4()->toBase32();
                $nestedTags[$uuid] = $item;
                $item = array_key_last($nestedTags);
            }
            $queryString .= $item;
        }

        // Restore = and &
        $queryString = str_replace(['&#61;', '&amp;'], ['=', '&'], $queryString);

        parse_str($queryString, $attributes);

        array_walk_recursive(
            $attributes,
            static function (&$value) use ($nestedTags): void {
                $items = [StringUtil::specialcharsAttribute($value)];

                foreach ($nestedTags as $uuid => $tag) {
                    $splitItems = [];

                    foreach ($items as $item) {
                        $item = explode($uuid, (string) $item, 2);
                        $splitItems[] = array_shift($item);

                        if ($item) {
                            $splitItems[] = $tag;
                            $splitItems[] = array_shift($item);
                        }
                    }
                    $items = $splitItems;
                }

                $value = new ParsedSequence($items);
            }
        );

        return $attributes;
    }

    private function callLegacyClass(string $input, bool $allowEsiTags): ChunkedText
    {
        if (null === $this->insertTags) {
            $this->framework->initialize();
            $this->insertTags = new InsertTags();
        }

        return $this->insertTags->replaceInternal($input, $allowEsiTags, $this);
    }

    private function handleLegacyTagsHook(ParsedSequence $input, bool $allowEsiTags): ParsedSequence
    {
        if (empty($GLOBALS['TL_HOOKS']['replaceInsertTags'])) {
            // return $input; TODO: enable once all insert tags are moved over
        }

        $hasLegacyTags = false;

        foreach ($input as $item) {
            if (!\is_string($item) && !$this->hasInsertTag($item->getName())) {
                $hasLegacyTags = true;
                break;
            }

            if (\is_string($item) && str_contains($item, '{{')) {
                $hasLegacyTags = true;
                break;
            }
        }

        if (!$hasLegacyTags) {
            return $input;
        }

        return $this->parse((string) $this->callLegacyClass($input->serialize(), $allowEsiTags));
    }

    private function handleLegacyFlagsHook(InsertTagResult $result, InsertTagFlag $flag, InsertTag $tag): InsertTagResult
    {
        // Set up as variables as they may be used by reference in the hooks
        $flags = array_map(static fn ($flag) => $flag->getName(), $tag->getFlags());
        $tags = ['', substr($tag->serialize(), 2, -2), ''];
        $rit = 0;
        $cnt = 3;
        $system = $this->framework->getAdapter(System::class);

        foreach ($GLOBALS['TL_HOOKS']['insertTagFlags'] ?? [] as $callback) {
            $hookResult = $system->importStatic($callback[0])->{$callback[1]}(
                $flag->getName(),
                $tag->getName().$tag->getParameters()->serialize(),
                $result->getValue(),
                $flags,
                false,
                $tags,
                [],
                $rit,
                $cnt,
            );

            // Replace the tag and stop the loop
            if (false !== $hookResult) {
                return new InsertTagResult(
                    (string) $hookResult,
                    OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text,
                    $result->getExpiresAt(),
                    $result->getCacheTags(),
                );
            }
        }

        $this->logger->error('Unknown insert tag flag "'.$flag->getName().'" in '.$tag->serialize().' on page '.$this->framework->getAdapter(Environment::class)->get('uri'));

        return $result;
    }
}
