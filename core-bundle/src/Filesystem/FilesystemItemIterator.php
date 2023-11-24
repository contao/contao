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
    private const MEDIA_TYPE_SORT_ORDER = [
        // Video
        'video/webm',
        'video/mp4',
        'video/quicktime',
        'video/x-ms-wmv',
        'video/ogg',

        // Audio
        'audio/mp4',
        'audio/m4a',
        'audio/x-m4a',
        'audio/mpeg',
        'audio/mp3',
        'audio/x-mp3',
        'audio/x-mpeg',
        'audio/x-mpg',
        'audio/x-ms-wma',
        'audio/wma',
        'audio/wav',
        'audio/vnd.wave',
        'audio/wave',
        'audio/x-wav',
        'audio/ogg',
        'audio/vorbis',
        'audio/x-flac+ogg',
        'audio/x-ogg',
        'audio/x-oggflac',
        'audio/x-speex+ogg',
        'audio/x-vorbis',
        'audio/x-vorbis+ogg',
    ];

    /**
     * @param iterable<FilesystemItem> $listing
     */
    public function __construct(private iterable $listing)
    {
        if ($listing instanceof \Traversable) {
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
                SortMode::mediaTypePriority => static fn (FilesystemItem $a, FilesystemItem $b): int => self::sortByMediaTypePriority($a, $b),
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
        foreach ($this->listing as $item) {
            return $item;
        }

        return null;
    }

    /**
     * @return \Traversable<FilesystemItem>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->listing as $item) {
            if (!$item instanceof FilesystemItem) {
                throw new \TypeError(sprintf('%s can only iterate over elements of type %s, got %s.', self::class, FilesystemItem::class, get_debug_type($item)));
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

    private static function sortByMediaTypePriority(FilesystemItem $a, FilesystemItem $b): int
    {
        $aIsFile = $a->isFile();

        if (0 !== ($sort = ($b->isFile() <=> $aIsFile))) {
            return $sort;
        }

        if (!$aIsFile) {
            return 0;
        }

        $sortOrderA = array_search($a->getMimeType(), self::MEDIA_TYPE_SORT_ORDER, true);
        $sortOrderB = array_search($b->getMimeType(), self::MEDIA_TYPE_SORT_ORDER, true);

        return (false === $sortOrderA ? PHP_INT_MAX : $sortOrderA) <=> (false === $sortOrderB ? PHP_INT_MAX : $sortOrderB);
    }
}
