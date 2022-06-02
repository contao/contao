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
use Contao\InsertTags;
use Contao\StringUtil;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Parse and replace insert tags.
 *
 * Formal syntax:
 *
 *     insert tag = '{{', tag contents, '}}'
 *     tag contents = name, parameter*, query?, flag*
 *     name = [a-z\x80-\xFF], [a-z0-9_\x80-\xFF]*
 *     parameter = '::', value
 *     query = '?', value char *
 *     flag = '|', value char *
 *     value = (value char | insert tag)*
 *     value char = [^{}|?]
 */
class InsertTagParser implements ResetInterface
{
    /**
     * @var array<string,InsertTagSubscription>
     */
    private array $subscriptions = [];

    public function __construct(private ContaoFramework $framework, private InsertTags|null $insertTags = null)
    {
    }

    public function addSubscription(InsertTagSubscription $subscription): void
    {
        $this->subscriptions[$subscription->name] = $subscription;
    }

    public function replace(string $input): string
    {
        $return = '';

        foreach ($this->parse($input) as $item) {
            if (\is_string($item)) {
                $return .= $item;
            } else {
                try {
                    $return .= $this->renderSubscription($item) ?? $item->serialize();
                } catch (\Throwable) {
                    // TODO: Throw and catch specific exceptions that are caused by legacy insert tags
                    $return .= $item->serialize();
                }
            }
        }

        return (string) $this->callLegacyClass($return, true);
    }

    public function replaceChunked(string $input): ChunkedText
    {
        return $this->callLegacyClass($input, true);
    }

    public function replaceInline(string $input): string
    {
        $return = '';

        foreach ($this->parse($input) as $item) {
            if (\is_string($item)) {
                $return .= $item;
            } else {
                try {
                    $return .= $this->renderSubscription($item) ?? $item->serialize();
                } catch (\Throwable) {
                    // TODO: Throw and catch specific exceptions that are caused by legacy insert tags
                    $return .= $item->serialize();
                }
            }
        }

        return (string) $this->callLegacyClass($return, false);
    }

    public function replaceInlineChunked(string $input): ChunkedText
    {
        return $this->callLegacyClass($input, false);
    }

    public function render(InsertTag|string $input): string
    {
        if ($input instanceof InsertTag) {
            $tag = $input;
        } else {
            try {
                $tag = $this->parseTag($input);
            } catch (\InvalidArgumentException) {
                // TODO: trigger_deprecation('contao/core-bundle', '5.0', $exception->getMessage().'. This will no longer work in Contao 6.0.');
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

        if (1 !== \count($chunked) || ChunkedText::TYPE_RAW !== $chunked[0][0] || !\is_string($chunked[0][1])) {
            throw new \RuntimeException('Rendering a single insert tag has to return a single raw chunk');
        }

        return $chunked[0][1];
    }

    private function renderSubscription(InsertTag $tag): string|null
    {
        if (!$subscription = $this->subscriptions[$tag->getName()] ?? null) {
            return null;
        }

        if (ProcessingMode::resolved === $subscription->mode) {
            $tag = $this->resolveNestedTags($tag);
        }

        return $subscription->service->{$subscription->method}($tag);
    }

    public function parse(string $input): ParsedSequence
    {
        if (
            !preg_match_all(
                <<<'EOD'
                    (
                        {{                # Starts with two opening curly braces
                        [a-z0-9\x80-\xFF] # The first letter must not be a reserved character of Twig, Mustache or similar template engines (see #805)
                        (?>[^{}]|(?R))*   # Match any character not curly brace or a nested insert tag
                        }}                # Ends with two closing curly braces
                    )x
                    EOD,
                $input,
                $matches,
                PREG_OFFSET_CAPTURE,
            )
        ) {
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
        $queryOnly = !$parameters && preg_match('/\?.+(?:=|&#61;)/s', $name);

        if ($queryOnly) {
            [$name, $parameters[0]] = preg_split('/(?=\?)/', $name, 2);
        }

        if (!preg_match('/^[a-z\x80-\xFF][a-z0-9_\x80-\xFF]*$/i', $name)) {
            throw new \InvalidArgumentException(sprintf('Invalid insert tag name "%s"', $name));
        }

        $insertTagRegex = /** @lang RegExp */ <<<'EOD'
            (?<it>                 # Named capturing group "it"
                {{                 # Starts with two opening curly braces
                [a-z0-9\x80-\xFF]  # The first letter must not be a reserved character of Twig, Mustache or similar template engines (see #805)
                (?>[^{}]|(?&it))*  # Match any character not curly brace or a nested insert tag
                }}                 # Ends with two closing curly braces
            )
            EOD;

        if ($parameters) {
            preg_match_all(
                <<<EOD
                    (
                        ::
                        (?:
                            [^{}|:]                # Match any character not curly brace, pipe or colon
                            |:(?!:)                # Or a single colon (not followed by another colon)
                            |$insertTagRegex       # Or an insert tag
                        )*
                        |.                         # Match anything else to detect syntax errors
                    )xs
                    EOD,
                '::'.$parameters[0],
                $parameterMatches,
            );

            foreach ($parameterMatches[0] ?? [''] as $index => $parameterMatch) {
                if (!str_starts_with($parameterMatch, '::')) {
                    throw new \InvalidArgumentException(sprintf('Invalid insert tag parameter syntax "%s"', $parameters[0]));
                }
                $parameterMatches[0][$index] = substr($parameterMatch, 2);
            }

            /** @var array<int,ParsedSequence> $parameters */
            $parameters = array_map($this->parse(...), $parameterMatches[0]);

            /* Discarded idea of query parameters in insert tags

            $paramSequence = [];
            $querySequence = [];

            foreach ($parameters[array_key_last($parameters)] as $sequenceItem) {
                if ($querySequence) {
                    $querySequence[] = $sequenceItem;
                } elseif (\is_string($sequenceItem) && preg_match('/\?.+(?:=|&#61;)/s', $sequenceItem)) {
                    $chunks = explode('?', $sequenceItem, 2);
                    $paramSequence[] = $chunks[0];
                    $querySequence[] = $chunks[1];
                } else {
                    $paramSequence[] = $sequenceItem;
                }
            }

            if ($queryOnly) {
                $parameters = [];
            } else {
                $parameters[array_key_last($parameters)] = new ParsedSequence($paramSequence);
            }

            $parameters += $this->parseQuery(new ParsedSequence($querySequence));
            */
        }

        if (strtolower($name) !== $name) {
            // TODO: trigger_deprecation('contao/core-bundle', '5.0', 'Insert tags with uppercase letters ("%s") have been deprecated and will no longer work in Contao 6.0.', $name);
            $name = strtolower($name);
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

                $value = $this->render($value);
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

    public function reset(): void
    {
        InsertTags::reset();
    }

    private function callLegacyClass(string $input, bool $allowEsiTags): ChunkedText
    {
        if (null === $this->insertTags) {
            $this->framework->initialize();
            $this->insertTags = new InsertTags();
        }

        return $this->insertTags->replaceInternal($input, $allowEsiTags);
    }
}
