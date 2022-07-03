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
use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Psr\Http\Message\UriInterface;
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
    public function has(Uuid|string $location, int $accessFlags = self::NONE): bool;

    /**
     * @throws VirtualFilesystemException
     */
    public function fileExists(Uuid|string $location, int $accessFlags = self::NONE): bool;

    /**
     * @throws VirtualFilesystemException
     */
    public function directoryExists(Uuid|string $location, int $accessFlags = self::NONE): bool;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function read(Uuid|string $location): string;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     *
     * @return resource
     */
    public function readStream(Uuid|string $location);

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function write(Uuid|string $location, string $contents, array $options = []): void;

    /**
     * @param resource $contents
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function writeStream(Uuid|string $location, $contents, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function delete(Uuid|string $location): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function deleteDirectory(Uuid|string $location): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function createDirectory(Uuid|string $location, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function copy(Uuid|string $source, string $destination, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function move(Uuid|string $source, string $destination, array $options = []): void;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function get(Uuid|string $location, int $accessFlags = self::NONE): FilesystemItem|null;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function listContents(Uuid|string $location, bool $deep = false, int $accessFlags = self::NONE): FilesystemItemIterator;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getLastModified(Uuid|string $location, int $accessFlags = self::NONE): int;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getFileSize(Uuid|string $location, int $accessFlags = self::NONE): int;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getMimeType(Uuid|string $location, int $accessFlags = self::NONE): string;

    /**
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     *
     * @return array<string,mixed>
     */
    public function getExtraMetadata(Uuid|string $location, int $accessFlags = self::NONE): array;

    /**
     * @param array<string,mixed> $metadata
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function setExtraMetadata(Uuid|string $location, array $metadata): void;

    /**
     * @throws UnableToResolveUuidException
     */
    public function generatePublicUri(Uuid|string $location, OptionsInterface $options = null): UriInterface|null;
}
