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

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTagFlag;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Environment;
use Contao\InsertTags;
use Contao\StringUtil;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
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
    private const TAG_REGEX = '
        (?\'it\'               # Named capturing group "it"
            {{                 # Starts with two opening curly braces
            [a-z0-9\x80-\xFF]  # The first letter must not be a reserved character of Twig, Mustache or similar template engines (see #805)
            (?>[^{}]|(?&it))*  # Match any character not curly brace or a nested insert tag
            }}                 # Ends with two closing curly braces
        )';

    private const PARAMETER_REGEX = '
        ::                        # Starts with double colon
        (?:
            [^{}|:]               # Match any character not curly brace, pipe or colon
            |:(?!:)               # Or a single colon (not followed by another colon)
            |'.self::TAG_REGEX.'  # Or an insert tag
        )*';

    /**
     * @var array<string, InsertTagSubscription>
     */
    private array $subscriptions = [];

    /**
     * @var array<string, InsertTagSubscription>
     */
    private array $blockSubscriptions = [];

    /**
     * @var array<string, \Closure(InsertTagFlag, InsertTagResult):InsertTagResult>
     */
    private array $flagCallbacks = [];

    private readonly string $allowedTagsRegex;

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly FragmentHandler $fragmentHandler,
        private readonly RequestStack $requestStack,
        private InsertTags|null $insertTags = null,
        array $allowedTags = ['*'],
    ) {
        $this->allowedTagsRegex = '('.implode(
            '|',
            array_map(
                static fn ($allowedTag) => '^'.implode('.+', array_map('preg_quote', explode('*', $allowedTag))).'$',
                $allowedTags ?: [''],
            ),
        ).')';
    }

    public function addSubscription(InsertTagSubscription $subscription): void
    {
        if (1 !== preg_match($this->allowedTagsRegex, $subscription->name)) {
            return;
        }

        if (isset($this->blockSubscriptions[$subscription->name])) {
            throw new \InvalidArgumentException(sprintf('The insert tag "%s" is already registered as a block insert tag.', $subscription->name));
        }

        $this->subscriptions[$subscription->name] = $subscription;
    }

    public function addBlockSubscription(InsertTagSubscription $subscription): void
    {
        if (1 !== preg_match($this->allowedTagsRegex, $subscription->name)) {
            return;
        }

        if (isset($this->subscriptions[$subscription->name])) {
            throw new \InvalidArgumentException(sprintf('The block insert tag "%s" is already registered as a regular insert tag.', $subscription->name));
        }

        $this->blockSubscriptions[$subscription->name] = $subscription;
    }

    public function addFlagCallback(string $name, object $service, string $method): void
    {
        $this->flagCallbacks[$name] = $service->$method(...);
    }

    /**
     * @return string may include <esi> tags
     */
    public function replace(ParsedSequence|string $input): string
    {
        return implode(
            '',
            array_map(
                static fn ($result) => OutputType::html !== $result->getOutputType() ? StringUtil::specialchars($result->getValue()) : $result->getValue(),
                $this->executeReplace($input, true, OutputType::html),
            ),
        );
    }

    /**
     * @return ChunkedText may include <esi> tags
     */
    public function replaceChunked(string $input): ChunkedText
    {
        return $this->toChunkedText($this->executeReplace($input, true));
    }

    /**
     * @return string does not include <esi> tags
     */
    public function replaceInline(ParsedSequence|string $input): string
    {
        return implode(
            '',
            array_map(
                static fn ($result) => OutputType::html !== $result->getOutputType() ? StringUtil::specialchars($result->getValue()) : $result->getValue(),
                $this->executeReplace($input, false, OutputType::html),
            ),
        );
    }

    /**
     * @return ChunkedText does not include <esi> tags
     */
    public function replaceInlineChunked(string $input): ChunkedText
    {
        return $this->toChunkedText($this->executeReplace($input, false));
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

        if ($tag && ($result = $this->renderSubscription($tag, false))) {
            return $result;
        }

        // Fallback to old implementation
        if ($input instanceof InsertTag) {
            $input = substr($input->serialize(), 2, -2);
        }

        $chunked = iterator_to_array($this->replaceInlineChunked('{{'.$input.'}}'));

        if (!$chunked) {
            return new InsertTagResult('', OutputType::text);
        }

        if (1 !== \count($chunked) || !\is_string($chunked[0][1])) {
            throw new \RuntimeException('Rendering a single insert tag has to return a single chunk');
        }

        return new InsertTagResult($chunked[0][1], ChunkedText::TYPE_RAW === $chunked[0][0] ? OutputType::html : OutputType::text);
    }

    /**
     * @internal
     */
    public function renderFlagForLegacyResult(string $flag, string $result): string|false
    {
        if (!$callback = $this->flagCallbacks[strtolower($flag)] ?? null) {
            return false;
        }

        return $callback(new InsertTagFlag($flag), new InsertTagResult($result, OutputType::html))->getValue();
    }

    public function parse(string $input): ParsedSequence
    {
        if (!$this->insertTags) {
            $this->framework->initialize();
            $this->insertTags = new InsertTags();
        }

        $input = $this->insertTags->encodeHtmlAttributes($input);

        return $this->doParse($input);
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
            array_map(static fn ($flag) => new InsertTagFlag($flag), $flags),
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
        return isset($this->subscriptions[$name]) || isset($this->blockSubscriptions[$name]);
    }

    private function doParse(string $input): ParsedSequence
    {
        if (!preg_match_all('('.self::TAG_REGEX.')ix', $input, $matches, PREG_OFFSET_CAPTURE)) {
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

    /**
     * @return list<InsertTagResult>
     */
    private function executeReplace(ParsedSequence|string $input, bool $allowEsiTags, OutputType $sourceType = OutputType::text): array
    {
        if (!$input instanceof ParsedSequence) {
            $input = $this->parse($input);
        }

        $input = $this->handleLegacyTagsHook($input, $allowEsiTags);

        $return = [];
        $wrapStart = null;
        $wrapContent = [];

        foreach ($input as $item) {
            if (
                $wrapStart
                && $item instanceof InsertTag
                && $item->getName() === $this->blockSubscriptions[$wrapStart->getName()]->endTag
            ) {
                $return = [
                    ...$return,
                    ...$this->executeReplace(
                        $this->renderBlockSubscription($wrapStart, new ParsedSequence($wrapContent)),
                        $allowEsiTags,
                        $sourceType,
                    ),
                ];

                $wrapStart = null;
                $wrapContent = [];

                // Reprocess non-empty end tags to enable chaining block insert tags
                // E.g. `{{iflng::de}}…{{iflng::en}}…{{iflng}}`
                if (!$item->getParameters()->all()) {
                    continue;
                }
            }

            if ($wrapStart) {
                $wrapContent[] = $item;

                continue;
            }

            if ($item instanceof InsertTagResult) {
                $return[] = $item;
                continue;
            }

            if (\is_string($item)) {
                $return[] = new InsertTagResult($item, $sourceType);
                continue;
            }

            if ($this->blockSubscriptions[$item->getName()] ?? false) {
                $wrapStart = $item;
                continue;
            }

            $return[] = $this->renderSubscription($item, $allowEsiTags) ?? new InsertTagResult($item->serialize(), OutputType::text);
        }

        // Missing end tag
        if ($wrapStart) {
            $return[] = new InsertTagResult($wrapStart->serialize(), OutputType::text);
            $return = [...$return, ...$this->executeReplace(new ParsedSequence($wrapContent), $allowEsiTags, $sourceType)];
        }

        return $return;
    }

    private function renderSubscription(InsertTag $tag, bool $allowEsiTags): InsertTagResult|null
    {
        if (!$subscription = $this->subscriptions[$tag->getName()] ?? null) {
            return null;
        }

        if ($allowEsiTags && $subscription->asFragment) {
            return $this->getFragmentForTag($tag);
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

    private function getFragmentForTag(InsertTag $tag): InsertTagResult
    {
        $attributes = ['insertTag' => $tag->serialize()];

        if ($scope = $this->requestStack->getCurrentRequest()?->attributes->get('_scope')) {
            $attributes['_scope'] = $scope;
        }

        $query = [
            'clientCache' => $GLOBALS['objPage']->clientCache ?? 0,
            'pageId' => $GLOBALS['objPage']->id ?? null,
            'request' => $this->requestStack->getCurrentRequest()?->getRequestUri(),
        ];

        $esiTag = $this->fragmentHandler->render(
            new ControllerReference(InsertTagsController::class.'::renderAction', $attributes, $query),
            'esi',
            ['ignore_errors' => false], // see #48
        );

        return new InsertTagResult($esiTag, OutputType::html);
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

                $value .= $this->renderTag($item)->getValue();
            }

            $resolvedParameters[] = $value;
        }

        return new ResolvedParameters($resolvedParameters);
    }

    /**
     * @param list<InsertTagResult> $results
     */
    private function toChunkedText(array $results): ChunkedText
    {
        $chunked = [];

        foreach ($results as $result) {
            if (OutputType::html === $result->getOutputType()) {
                $chunked[] = '';
                $chunked[] = $result->getValue();
            } else {
                $chunked[] = $result->getValue();
                $chunked[] = '';
            }
        }

        return new ChunkedText($chunked);
    }

    private function callLegacyClass(string $input, bool $allowEsiTags): ChunkedText
    {
        if (!$this->insertTags) {
            $this->framework->initialize();
            $this->insertTags = new InsertTags();
        }

        return $this->insertTags->replaceInternal($input, $allowEsiTags, $this);
    }

    private function handleLegacyTagsHook(ParsedSequence $input, bool $allowEsiTags): ParsedSequence
    {
        if (empty($GLOBALS['TL_HOOKS']['replaceInsertTags'])) {
            return $input;
        }

        $hasLegacyTags = false;

        foreach ($input as $item) {
            if (
                (\is_string($item) && str_contains($item, '{{'))
                || ($item instanceof InsertTag && !$this->hasInsertTag($item->getName()))
            ) {
                $hasLegacyTags = true;
                break;
            }
        }

        if (!$hasLegacyTags) {
            return $input;
        }

        $outputs = [];

        foreach ($this->callLegacyClass($input->serialize(), $allowEsiTags) as [$type, $chunk]) {
            $outputs[] = match ($type) {
                ChunkedText::TYPE_TEXT => iterator_to_array($this->doParse($chunk)),
                ChunkedText::TYPE_RAW => [new InsertTagResult($chunk, OutputType::html)],
            };
        }

        return new ParsedSequence(array_merge(...$outputs));
    }

    private function handleLegacyFlagsHook(InsertTagResult $result, InsertTagFlag $flag, InsertTag $tag): InsertTagResult
    {
        if (!isset($GLOBALS['TL_HOOKS']['insertTagFlags']) || !\is_array($GLOBALS['TL_HOOKS']['insertTagFlags'])) {
            return $result;
        }

        trigger_deprecation('contao/core-bundle', '5.2', 'Using the "insertTagFlags" hook has been deprecated and will no longer work in Contao 6.0. Use the "%s" attribute instead.', AsInsertTagFlag::class);

        // Set up as variables as they may be used by reference in the hooks
        $flagName = $flag->getName();
        $tagNameAndParameters = $tag->getName().$tag->getParameters()->serialize();
        $currentResult = $result->getValue();
        $flags = array_map(static fn ($flag) => $flag->getName(), $tag->getFlags());
        $allowEsiTags = false;
        $tags = ['', substr($tag->serialize(), 2, -2), ''];
        $rit = 0;
        $cnt = 3;

        foreach ($GLOBALS['TL_HOOKS']['insertTagFlags'] as $callback) {
            $hookResult = System::importStatic($callback[0])->{$callback[1]}(
                $flagName,
                $tagNameAndParameters,
                $currentResult,
                $flags,
                $allowEsiTags,
                $tags,
                [],
                $rit,
                $cnt,
            );

            // Replace the tag and stop the loop
            if (false !== $hookResult) {
                return $result
                    ->withValue((string) $hookResult)
                    ->withOutputType(OutputType::html === $result->getOutputType() ? OutputType::html : OutputType::text)
                ;
            }
        }

        $this->logger->error('Unknown insert tag flag "'.$flag->getName().'" in '.$tag->serialize().' on page '.$this->framework->getAdapter(Environment::class)->get('uri'));

        return $result;
    }
}
