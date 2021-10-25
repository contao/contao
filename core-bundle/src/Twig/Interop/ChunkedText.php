<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Interop;

/**
 * @experimental
 */
class ChunkedText implements \IteratorAggregate
{
    public const TYPE_TEXT = 0;
    public const TYPE_RAW = 1;

    /**
     * @var array<int, array{0:int, 1:string}>
     */
    private $chunks;

    public function __construct(array $chunks)
    {
        $this->chunks = $chunks;
    }

    public function __toString(): string
    {
        return implode('', $this->chunks, );
    }

    /**
     * @return \Generator<array{0:int, 1:string}>
     */
    public function getIterator(): \Generator
    {
        for ($i = 0; $i < \count($this->chunks); $i += 2) {
            if ('' !== ($raw = $this->chunks[$i + 1])) {
                yield [self::TYPE_RAW, $raw];
            }

            if ('' !== ($text = $this->chunks[$i])) {
                yield [self::TYPE_TEXT, $text];
            }
        }
    }
}
