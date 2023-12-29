<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
interface DbafsInterface
{
    public const FEATURES_NONE = 0;

    public const FEATURE_LAST_MODIFIED = 1 << 0;

    public const FEATURE_FILE_SIZE = 1 << 1;

    public const FEATURE_MIME_TYPE = 1 << 2;

    /**
     * Resolves a UUID to a path, returns null if it does not exist.
     */
    public function getPathFromUuid(Uuid $uuid): string|null;

    /**
     * Returns a record or null if none was found.
     *
     * The given $path must be relative to the DBAFS root.
     */
    public function getRecord(string $path): FilesystemItem|null;

    /**
     * Returns an iterator over all records inside $path.
     *
     * The given $path must be relative to the DBAFS root. If $deep is true,
     * this also includes all subdirectories (recursively).
     *
     * @return iterable<FilesystemItem>
     */
    public function getRecords(string $path, bool $deep = false): iterable;

    /**
     * Sets extra metadata for a record.
     *
     * The given array may contain additional keys that simply will be ignored
     * if they do not match the internal data structure.
     *
     * The given $path must be relative to the DBAFS root.
     *
     * @param array<string, mixed> $metadata
     *
     * @throws \InvalidArgumentException if provided $path is invalid
     */
    public function setExtraMetadata(string $path, array $metadata): void;

    /**
     * Updates the DBAFS database.
     *
     * By providing $paths, you can indicate that only certain files or
     * directories need to be synchronized (performance). The DBAFS
     * implementation may however include additional resources.
     *
     * All $paths must be relative to the DBAFS root and can occur in one of
     * the following forms:
     *
     *    'foo/bar/baz' -> just the single file/directory "foo/bar/baz"
     *    'foo/**' -> "foo" and all child resources in all subdirectories
     *    'foo/*' -> "foo" and only direct child resources of "foo"
     */
    public function sync(string ...$paths): ChangeSet;

    /**
     * Returns combined binary flags of all features this implementation does
     * support.
     *
     * For each feature, the respective values are expected to be set in the
     * returned items.
     *
     * Example:
     *
     *    public function getSupportedFeatures(): int {
     *        // We support "file size" and "mime type" and set it in each record.
     *        return DbafsInterface::FEATURE_FILE_SIZE | DbafsInterface::FEATURE_MIME_TYPE;
     *    }
     */
    public function getSupportedFeatures(): int;
}
