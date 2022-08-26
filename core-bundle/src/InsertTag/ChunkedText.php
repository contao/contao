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

/**
 * @implements \IteratorAggregate<int, array{0:int, 1:string}>
 */
final class ChunkedText implements \IteratorAggregate
{
    public const TYPE_TEXT = 0;
    public const TYPE_RAW = 1;

    /**
     * @var array<int, string>
     */
    private array $chunks;

    /**
     * @internal
     */
    public function __construct(array $chunks)
    {
        $this->chunks = $chunks;
    }

    public function __toString(): string
    {
        return implode('', $this->chunks);
    }

    /**
     * @return \Generator<array{0:int, 1:string}>
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
