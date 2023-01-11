<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Util;

/**
 * @template TKey
 * @template TValue
 *
 * @implements \IteratorAggregate<TKey, TValue>
 */
class CachingTraversable implements \IteratorAggregate
{
    /**
     * @var list<array{0:TKey, 1:TValue}>
     */
    private array $items = [];

    /**
     * @var \IteratorIterator<TKey, TValue, \Traversable<TKey, TValue>>
     */
    private \IteratorIterator $iterator;

    /**
     * @param \Traversable<TKey, TValue> $traversable
     */
    public function __construct(\Traversable $traversable)
    {
        $this->iterator = new \IteratorIterator($traversable);
    }

    /**
     * @return \Generator<TKey, TValue>
     */
    public function getIterator(): \Generator
    {
        $current = 0;

        while (true) {
            if (isset($this->items[$current])) {
                yield $this->items[$current][0] => $this->items[$current][1];
                ++$current;

                continue;
            }

            if (0 === $current) {
                $this->iterator->rewind();
            } else {
                $this->iterator->next();
            }

            if (!$this->iterator->valid()) {
                return;
            }

            $this->items[$current] = [$this->iterator->key(), $this->iterator->current()];
        }
    }
}
