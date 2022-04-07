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

    public function sort(SortMode $sortMode = SortMode::pathAscending): self
    {
        $listing = $this->toArray();

        usort(
            $listing,
            match ($sortMode) {
                SortMode::pathAscending => static fn (FilesystemItem $a, FilesystemItem $b): int => strcasecmp($a->getPath(), $b->getPath()),
                SortMode::pathDescending => static fn (FilesystemItem $a, FilesystemItem $b): int => -strcasecmp($a->getPath(), $b->getPath()),
                SortMode::pathNaturalAscending => static fn (FilesystemItem $a, FilesystemItem $b): int => strnatcasecmp($a->getPath(), $b->getPath()),
                SortMode::pathNaturalDescending => static fn (FilesystemItem $a, FilesystemItem $b): int => -strnatcasecmp($a->getPath(), $b->getPath()),
                SortMode::lastModifiedAscending => static fn (FilesystemItem $a, FilesystemItem $b): int => $a->getLastModified() <=> $b->getLastModified(),
                SortMode::lastModifiedDescending => static fn (FilesystemItem $a, FilesystemItem $b): int => $b->getLastModified() <=> $a->getLastModified(),
            },
        );

        return new self($listing);
    }

    /**
     * @param callable(FilesystemItem):bool $condition
     */
    public function any(callable $condition): bool
    {
        foreach ($this as $item) {
            if ($condition($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(FilesystemItem):bool $condition
     */
    public function all(callable $condition): bool
    {
        foreach ($this as $item) {
            if (!$condition($item)) {
                return false;
            }
        }

        return true;
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
