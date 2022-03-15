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
     * @param iterable<FilesystemItem> $listing
     */
    public function __construct(private iterable $listing)
    {
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
     * @return \Traversable<FilesystemItem>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->listing as $item) {
            if (!$item instanceof FilesystemItem) {
                /** @phpstan-ignore-next-line */
                $type = \is_object($item) ? $item::class : \gettype($item);

                throw new \TypeError(sprintf('%s can only iterate over elements of type %s, got %s.', self::class, FilesystemItem::class, $type));
            }

            yield $item;
        }
    }

    /**
     * @return array<FilesystemItem>
     */
    public function toArray(): array
    {
        return iterator_to_array($this, false);
    }
}
