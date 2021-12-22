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

use Contao\CoreBundle\Filesystem\FilesystemItem;
use Symfony\Component\Uid\Uuid;

interface DbafsInterface
{
    /**
     * Resolves a UUID to a path, returns null if it does not exist.
     */
    public function getPathFromUuid(Uuid $uuid): ?string;

    /**
     * Returns a record or null if none was found.
     */
    public function getRecord(string $path): ?FilesystemItem;

    /**
     * Returns an iterator over all records inside $path. If $deep is true,
     * this also includes all subdirectories (recursively).
     *
     * @return iterable<FilesystemItem>
     */
    public function getRecords(string $path, bool $deep = false): iterable;

    /**
     * Sets extra metadata for a record. The given array may contain additional
     * keys that simply will be ignored if they do not match the internal data
     * structure.
     *
     * @param array<string, mixed> $metadata
     *
     * @throws \InvalidArgumentException if provided $path is invalid
     */
    public function setExtraMetadata(string $path, array $metadata): void;

    /**
     * Synchronizes the database with the configured filesystem. If $scope
     * paths are provided only certain files/directories will be synchronized.
     *
     * Paths can have the following forms:
     *
     *   'foo/bar/baz' = just the single the file/directory foo/bar/baz
     *   'foo/**' = foo and all resources in all subdirectories
     *   'foo/*' = foo and only direct child resources of foo
     *
     * @param string ...$paths relative paths inside the filesystem root
     */
    public function sync(string ...$paths): ChangeSet;

    /**
     * Returns true if this DBAFS sets the key 'lastModified' in the returned records.
     */
    public function supportsLastModified(): bool;

    /**
     * Returns true if this DBAFS sets the key 'fileSize' in the returned records.
     */
    public function supportsFileSize(): bool;

    /**
     * Returns true if this DBAFS sets the key 'mimeType' in the returned records.
     */
    public function supportsMimeType(): bool;
}
