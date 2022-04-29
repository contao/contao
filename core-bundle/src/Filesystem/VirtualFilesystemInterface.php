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
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     */
    public function has($location, int $accessFlags = self::NONE): bool;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     */
    public function fileExists($location, int $accessFlags = self::NONE): bool;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     */
    public function directoryExists($location, int $accessFlags = self::NONE): bool;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function read($location): string;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     *
     * @return resource
     */
    public function readStream($location);

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function write($location, string $contents, array $options = []): void;

    /**
     * @param string|Uuid $location
     * @param resource    $contents
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function writeStream($location, $contents, array $options = []): void;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function delete($location): void;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function deleteDirectory($location): void;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function createDirectory($location, array $options = []): void;

    /**
     * @param string|Uuid $source
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function copy($source, string $destination, array $options = []): void;

    /**
     * @param string|Uuid $source
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function move($source, string $destination, array $options = []): void;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function get($location, int $accessFlags = self::NONE): ?FilesystemItem;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function listContents($location, bool $deep = false, int $accessFlags = self::NONE): FilesystemItemIterator;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getLastModified($location, int $accessFlags = self::NONE): int;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getFileSize($location, int $accessFlags = self::NONE): int;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function getMimeType($location, int $accessFlags = self::NONE): string;

    /**
     * @param string|Uuid $location
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     *
     * @return array<string,mixed>
     */
    public function getExtraMetadata($location, int $accessFlags = self::NONE): array;

    /**
     * @param string|Uuid         $location
     * @param array<string,mixed> $metadata
     *
     * @throws VirtualFilesystemException
     * @throws UnableToResolveUuidException
     */
    public function setExtraMetadata($location, array $metadata): void;

    /**
     * @param string|Uuid $location
     *
     * @throws UnableToResolveUuidException
     */
    public function generatePublicUri($location, OptionsInterface $options = null): ?UriInterface;
}
