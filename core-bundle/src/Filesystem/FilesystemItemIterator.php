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

use Contao\CoreBundle\Util\CachingTraversable;

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
        if (!\is_array($listing)) {
            $this->listing = new CachingTraversable($listing);
        }
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

    public function limit(int $numberOfElements): self
    {
        if ($numberOfElements < 0) {
            throw new \OutOfRangeException(sprintf('Illegal limit value "%d", must be greater or equal to zero.', $numberOfElements));
        }

        $listLimited = static function (iterable $listing) use ($numberOfElements): \Generator {
            $count = 0;

            foreach ($listing as $item) {
                if (++$count > $numberOfElements) {
                    return;
                }

                yield $item;
            }
        };

        return new self($listLimited($this->listing));
    }

    public function first(): FilesystemItem|null
    {
        if (!\is_array($this->listing)) {
            $this->listing = iterator_to_array($this->listing);
        }

        return $this->listing[0] ?? null;
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
