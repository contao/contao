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

class InsertTagParser implements ResetInterface
{
    public function __construct(private ContaoFramework $framework, private InsertTags|null $insertTags = null)
    {
    }

    public function replace(string $input): string
    {
        $return = '';
        $failed = false;

        foreach ($this->parse($input) as $item) {
            if (\is_string($item)) {
                $return .= (string) $this->callLegacyClass($item, true);
            } else {
                try {
                    $return .= $this->render($item);
                } catch (\Throwable) {
                    // TODO: Throw and catch specific exceptions that are caused by legacy insert tags
                    $failed = true;
                    $return .= $item->serialize();
                }
            }
        }

        if ($failed) {
            $return = (string) $this->callLegacyClass($return, true);
        }

        return $return;
    }

    public function replaceChunked(string $input): ChunkedText
    {
        return $this->callLegacyClass($input, true);
    }

    public function replaceInline(string $input): string
    {
        return (string) $this->replaceInlineChunked($input);
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
            // TODO: call tagged services
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
        if (preg_match('/\|[^{}]*$/', $insertTag, $flags)) {
            $insertTag = substr($insertTag, 0, \strlen($insertTag) - \strlen((string) $flags[0]));
            $flags = explode('|', substr($flags[0], 1));
        } else {
            $flags = [];
        }

        $parameters = explode('::', $insertTag, 2);
        $name = array_shift($parameters);

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

            $parameters = array_map($this->parse(...), $parameterMatches[0]);

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
            $parameters[array_key_last($parameters)] = new ParsedSequence($paramSequence);
            $parameters += $this->parseQuery(new ParsedSequence($querySequence));
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

        foreach ($parameters->keys() as $key) {
            $parameter = $parameters->get($key);

            if (!$parameter instanceof ParsedSequence) {
                $resolvedParameters[$key] = $this->resolveParameters($parameter);

                continue;
            }

            $value = '';

            foreach ($parameter as $item) {
                if (\is_string($item)) {
                    $value .= $item;

                    continue;
                }

                $value = $this->render($value);
            }

            if ((string) (int) $value === $value) {
                $value = (int) $value;
            } elseif ((string) (float) $value === $value) {
                $value = (float) $value;
            }

            $resolvedParameters[$key] = $value;
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
