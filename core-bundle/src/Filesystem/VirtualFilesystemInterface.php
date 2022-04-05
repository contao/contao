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
use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
interface VirtualFilesystemInterface
{
    public const NONE = 0;
    public const BYPASS_DBAFS = 1 << 0;
    public const FORCE_SYNC = 1 << 1;

    /**
     * @throws VirtualFilesystemException
     */
    public function has(string|Uuid $location, int $accessFlags = self::NONE): bool;

    /**
     * @throws VirtualFilesystemException
     */
    public function fileExists(string|Uuid $location, int $accessFlags = self::NONE): bool;

    /**
     * @throws VirtualFilesystemException
     */
    public function directoryExists(string|Uuid $location, int $accessFlags = self::NONE): bool;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function read(string|Uuid $location): string;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     *
     * @return resource
     */
    public function readStream(string|Uuid $location);

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function write(string|Uuid $location, string $contents, array $options = []): void;

    /**
     * @param resource $contents
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function writeStream(string|Uuid $location, $contents, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function delete(string|Uuid $location): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function deleteDirectory(string|Uuid $location): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function createDirectory(string|Uuid $location, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function copy(string|Uuid $source, string $destination, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function move(string|Uuid $source, string $destination, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function get(string|Uuid $location, int $accessFlags = self::NONE): ?FilesystemItem;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function listContents(string|Uuid $location, bool $deep = false, int $accessFlags = self::NONE): FilesystemItemIterator;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getLastModified(string|Uuid $location, int $accessFlags = self::NONE): int;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getFileSize(string|Uuid $location, int $accessFlags = self::NONE): int;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getMimeType(string|Uuid $location, int $accessFlags = self::NONE): string;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     *
     * @return array<string,mixed>
     */
    public function getExtraMetadata(string|Uuid $location, int $accessFlags = self::NONE): array;

    /**
     * @param array<string,mixed> $metadata
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function setExtraMetadata(string|Uuid $location, array $metadata): void;
}
