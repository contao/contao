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

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToRetrieveMetadata;
use Symfony\Component\Uid\Uuid;

/**
 * @phpstan-import-type ExtraMetadata from DbafsInterface
 */
interface DbafsFilesystemOperator extends FilesystemOperator
{
    public const SYNCED_ONLY = 0;
    public const BYPASS_DBAFS = 1;
    public const FORCE_SYNC = 2;

    /**
     * {@inheritdoc}f
     *
     * @param string|Uuid $location
     */
    public function fileExists($location, int $accessType = self::SYNCED_ONLY): bool;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function read($location): string;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function readStream($location);

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function listContents($location, bool $deep = self::LIST_SHALLOW, int $accessType = self::SYNCED_ONLY): DirectoryListing;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $path
     */
    public function lastModified($path, int $accessType = self::SYNCED_ONLY): int;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $path
     */
    public function fileSize($path, int $accessType = self::SYNCED_ONLY): int;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $path
     */
    public function mimeType($path, int $accessType = self::SYNCED_ONLY): string;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $path
     */
    public function visibility($path): string;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function write($location, string $contents, array $config = []): void;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function writeStream($location, $contents, array $config = []): void;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $path
     */
    public function setVisibility($path, string $visibility): void;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function delete($location): void;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function deleteDirectory($location): void;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $location
     */
    public function createDirectory($location, array $config = []): void;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $source
     */
    public function move($source, string $destination, array $config = []): void;

    /**
     * {@inheritdoc}
     *
     * @param string|Uuid $source
     */
    public function copy($source, string $destination, array $config = []): void;

    /**
     * @param string|Uuid $location
     * @phpstan-return ExtraMetadata
     *
     * @throws UnableToRetrieveMetadata
     * @throws FilesystemException
     */
    public function extraMetadata($location, int $accessType = self::SYNCED_ONLY): array;

    /**
     * @param string|Uuid $location
     * @phpstan-param ExtraMetadata $metadata
     *
     * @throws UnableToSetExtraMetadata
     * @throws FilesystemException
     */
    public function setExtraMetadata($location, array $metadata): void;
}
