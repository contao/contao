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

use League\Flysystem\FilesystemAdapter;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-type ExtraMetadata array<string, mixed>
 * @phpstan-type Record array{isFile: bool, path: string, lastModified: ?int, fileSize: ?int, mimeType: ?string, extra: ExtraMetadata}
 */
interface DbafsInterface
{
    /**
     * Resolves a UUID to a path, returns null if it does not exist.
     */
    public function getPathFromUuid(Uuid $uuid): ?string;

    /**
     * Returns a record or null if none was found.
     *
     * @phpstan-return Record|null
     */
    public function getRecord(string $path): ?array;

    /**
     * Returns an iterator over all records inside $path. If $deep is true,
     * this also includes all subdirectories (recursively).
     *
     * @return \Generator<array>
     * @phpstan-return \Generator<Record>
     */
    public function getRecords(string $path, bool $deep = false): \Generator;

    /**
     * Sets extra metadata for a record.
     *
     * @phpstan-param ExtraMetadata $metadata
     *
     * @throws \InvalidArgumentException if provided $path or $metadata is invalid
     */
    public function setExtraMetadata(string $path, array $metadata): void;

    /**
     * Synchronizes the database with a given filesystem adapter. If $scope
     * paths are provided only certain files/directories will be synchronized.
     *
     * Paths can have the following forms:
     *
     *   'foo/bar/baz' = just the single the file/directory foo/bar/baz
     *   'foo/**' = foo and all resources in all subdirectories
     *   'foo/*' = foo and only direct child resources of foo
     *
     * @param string ...$scope relative paths inside the filesystem root
     */
    public function sync(FilesystemAdapter $filesystem, string ...$scope): ChangeSet;

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
