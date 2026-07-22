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

trigger_deprecation('contao/core-bundle', '6.0', 'Using "%s" is deprecated and will no longer work in Contao 7.', ChunkedText::class);

/**
 * @implements \IteratorAggregate<int, array{0: self::TYPE_*, 1: string}>
 *
 * @deprecated Deprecated since Contao 6.0, to be removed in Contao 7.
 */
final class ChunkedText implements \IteratorAggregate, \Stringable
{
    public const TYPE_TEXT = 0;

    public const TYPE_RAW = 1;

    /**
     * @param array<string> $chunks
     *
     * @internal
     */
    public function __construct(private readonly array $chunks)
    {
    }

    public function __toString(): string
    {
        return implode('', $this->chunks);
    }

    /**
     * @param list<array{0: self::TYPE_*, 1: string}> $chunks
     *
     * @internal
     */
    public static function fromTypedChunks(array $chunks): self
    {
        $indexedArray = [];

        foreach ($chunks as [$type, $chunk]) {
            if ((bool) (\count($indexedArray) % 2) === (self::TYPE_TEXT === $type)) {
                $indexedArray[] = '';
            }

            $indexedArray[] = $chunk;
        }

        return new self($indexedArray);
    }

    /**
     * @return \Generator<array{0: self::TYPE_*, 1: string}>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->chunks as $index => $chunk) {
            if ('' === $chunk) {
                continue;
            }

            yield [$index % 2 ? self::TYPE_RAW : self::TYPE_TEXT, $chunk];
        }
    }
}
