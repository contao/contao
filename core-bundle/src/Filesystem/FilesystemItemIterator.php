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
    public const SORT_BY_PATH_ASC = 'name_asc';
    public const SORT_BY_PATH_DESC = 'name_desc';
    public const SORT_BY_LAST_MODIFIED_ASC = 'date_asc';
    public const SORT_BY_LAST_MODIFIED_DESC = 'date_desc';

    public static array $supportedSortingModes = [
        self::SORT_BY_PATH_ASC,
        self::SORT_BY_PATH_DESC,
        self::SORT_BY_LAST_MODIFIED_ASC,
        self::SORT_BY_LAST_MODIFIED_DESC,
    ];
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

    public function sort(string $sortingMode = self::SORT_BY_PATH_ASC): self
    {
        $listing = $this->toArray();

        match ($sortingMode) {
            self::SORT_BY_PATH_ASC => usort($listing, static fn (FilesystemItem $a, FilesystemItem $b): int => strnatcasecmp($a->getPath(), $b->getPath())),
            self::SORT_BY_PATH_DESC => usort($listing, static fn (FilesystemItem $a, FilesystemItem $b): int => -strnatcasecmp($a->getPath(), $b->getPath())),
            self::SORT_BY_LAST_MODIFIED_ASC => usort($listing, static fn (FilesystemItem $a, FilesystemItem $b): int => $a->getLastModified() <=> $b->getLastModified()),
            self::SORT_BY_LAST_MODIFIED_DESC => usort($listing, static fn (FilesystemItem $a, FilesystemItem $b): int => $b->getLastModified() <=> $a->getLastModified()),
            default => throw new \InvalidArgumentException(sprintf('Unsupported sorting mode "%s", must be one of "%s".', $sortingMode, implode('", "', self::$supportedSortingModes)))
        };

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
                $type = \is_object($item) ? \get_class($item) : \gettype($item);

                throw new \TypeError(sprintf('%s can only iterate over elements of type %s, got %s.', __CLASS__, FilesystemItem::class, $type));
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
