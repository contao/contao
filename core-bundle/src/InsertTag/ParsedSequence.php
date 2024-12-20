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
 * @implements \IteratorAggregate<int,InsertTag|InsertTagResult|string>
 */
final class ParsedSequence implements \IteratorAggregate, \Countable
{
    /**
     * @var list<InsertTag|InsertTagResult|string>
     */
    private readonly array $sequence;

    /**
     * @param list<InsertTag|InsertTagResult|string> $sequence
     */
    public function __construct(array $sequence)
    {
        $this->sequence = array_values(array_filter($sequence, static fn ($item) => '' !== $item));
    }

    public function get(int $index): InsertTag|InsertTagResult|string
    {
        return $this->sequence[$index] ?? throw new \InvalidArgumentException(\sprintf('Index "%s" not exists', $index));
    }

    public function count(): int
    {
        return \count($this->sequence);
    }

    public function hasInsertTags(): bool
    {
        foreach ($this->sequence as $item) {
            if ($item instanceof InsertTag) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return \ArrayIterator<int, InsertTag|InsertTagResult|string>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->sequence);
    }

    public function serialize(): string
    {
        $serialized = '';

        foreach ($this as $item) {
            $serialized .= match (true) {
                $item instanceof InsertTag => $item->serialize(),
                $item instanceof InsertTagResult => $item->getValue(),
                default => (string) $item,
            };
        }

        return $serialized;
    }
}
