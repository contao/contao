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

use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\StringUtil;
use Contao\Validator;
use League\Flysystem\Filesystem;
use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
class FilesystemUtil
{
    /**
     * Gets all files from a serialized string or array of binary UUIDs like
     * for instance stored in "tl_content.multiSRC".
     *
     * The following rules apply:
     *  - Invalid or non-existent UUIDs are skipped without producing an error.
     *  - If the UUID points to a directory, its file contents are used (only first level).
     *  - Duplicate files are stripped.
     *
     * @param string|array<string|null> $sources
     */
    public static function listContentsFromSerialized(VirtualFilesystemInterface $storage, array|string $sources): FilesystemItemIterator
    {
        $uuids = array_filter(StringUtil::deserialize($sources, true));

        return new FilesystemItemIterator(self::doListContentsFromUuids($storage, $uuids));
    }

    /**
     * @internal
     *
     * @see Filesystem::assertIsResource()
     */
    public static function assertIsResource(mixed $contents): void
    {
        if (!\is_resource($contents)) {
            $type = \gettype($contents);

            throw new \LogicException(sprintf('Invalid stream provided, expected stream resource, received "%s".', $type));
        }

        if ('stream' !== ($type = get_resource_type($contents))) {
            throw new \LogicException(sprintf('Invalid stream provided, expected stream resource, received resource of type "%s".', $type));
        }
    }

    /**
     * @param resource $resource
     *
     * @internal
     *
     * @see Filesystem::rewindStream()
     */
    public static function rewindStream($resource): void
    {
        if (0 !== ftell($resource) && stream_get_meta_data($resource)['seekable']) {
            rewind($resource);
        }
    }

    /**
     * @param array<string> $uuids
     *
     * @return \Generator<FilesystemItem>
     */
    private static function doListContentsFromUuids(VirtualFilesystemInterface $storage, array $uuids): \Generator
    {
        $paths = [];

        // Keeps track of items; returns true if an item was encountered the first time
        $track = static function (FilesystemItem $item) use (&$paths): bool {
            if ($new = !\in_array($path = $item->getPath(), $paths, true)) {
                $paths[] = $path;
            }

            return $new;
        };

        foreach ($uuids as $uuid) {
            if (!\is_string($uuid)) {
                continue;
            }

            if (Validator::isBinaryUuid($uuid)) {
                $uuid = StringUtil::binToUuid($uuid);
            }

            try {
                $uuidObject = Uuid::isValid($uuid) ? Uuid::fromString($uuid) : Uuid::fromBinary($uuid);

                if (null === ($item = $storage->get($uuidObject))) {
                    continue;
                }
            } catch (\InvalidArgumentException|UnableToResolveUuidException) {
                continue;
            }

            if ($item->isFile()) {
                if ($track($item)) {
                    yield $item;
                }

                continue;
            }

            // If the item is a directory, yield its files instead
            $listDirectory = $storage
                ->listContents($item->getPath())
                ->files()
                ->filter(static fn (FilesystemItem $item): bool => $track($item))
            ;

            foreach ($listDirectory as $file) {
                yield $file;
            }
        }
    }
}
