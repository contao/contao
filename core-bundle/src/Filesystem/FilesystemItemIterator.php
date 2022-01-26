<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem;

/**
 * @implements \IteratorAggregate<int, FilesystemItem>
 */
class FilesystemItemIterator implements \IteratorAggregate
{
    /**
     * @var iterable<FilesystemItem>
     */
    private iterable $listing;

    /**
     * @param iterable<FilesystemItem> $listing
     */
    public function __construct(iterable $listing)
    {
        $this->listing = $listing;
    }

    /**
     * @param callable(FilesystemItem):bool $filter
     */
    public function filter(callable $filter): self
    {
        $listFiltered = static function (iterable $listing) use ($filter): \Generator {
            foreach ($listing as $item) {
                if ($filter($item)) {
                    yield $item;
                }
            }
        };

        return new self($listFiltered($this->listing));
    }

    public function files(): self
    {
        return $this->filter(static fn (FilesystemItem $item) => $item->isFile());
    }

    public function directories(): self
    {
        return $this->filter(static fn (FilesystemItem $item) => !$item->isFile());
    }

    /**
     * @return iterable<FilesystemItem>
     */
    public function getIterator(): iterable
    {
        return $this->listing instanceof \Traversable ?
            $this->listing : new \ArrayIterator($this->listing);
    }

    /**
     * @return array<FilesystemItem>
     */
    public function toArray(): array
    {
        return $this->listing instanceof \Traversable ?
            iterator_to_array($this->listing, false) : (array) $this->listing;
    }
}
