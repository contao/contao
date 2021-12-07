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

use Contao\StringUtil;
use Contao\Validator;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\InvalidStreamProvided;
use League\Flysystem\PathNormalizer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\WhitespacePathNormalizer;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * This Flysystem operator wraps a DBAFS layer around the underlying filesystem
 * adapter: Listing content will only access the database, operations that can
 * mutate the filesystem, will automatically trigger a synchronization.
 *
 * Additionally, there is support to directly retrieve the 'extra metadata'
 * from a file's / directory's StorageAttributes via a dedicated method.
 *
 * We widen the FilesystemOperator interface - instead of calling methods with
 * resource paths (string), you can also pass in an ID (int) or UUID of a
 * synchronized DBAFS resource.
 *
 * @phpstan-import-type ExtraMetadata from Dbafs
 */
final class Filesystem implements FilesystemOperator
{
    private FilesystemAdapter $adapter;
    private Config $config;
    private PathNormalizer $pathNormalizer;
    private Dbafs $dbafs;
    private LazyLoadingGhostFactory $proxyFactory;

    public function __construct(Dbafs $dbafs, FilesystemAdapter $adapter, LazyLoadingGhostFactory $proxyFactory = null, Config $config = null, PathNormalizer $pathNormalizer = null)
    {
        $this->dbafs = $dbafs;
        $this->adapter = $adapter;
        $this->proxyFactory = $proxyFactory ?? new LazyLoadingGhostFactory();
        $this->config = $config ?? new Config();
        $this->pathNormalizer = $pathNormalizer ?? new WhitespacePathNormalizer();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function fileExists($location): bool
    {
        try {
            $path = $this->resolvePath($location);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return null !== $this->dbafs->getRecord($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function write($location, string $contents, array $config = []): void
    {
        $path = $this->resolvePath($location);

        $this->adapter->write($path, $contents, $this->mergeConfig($config));
        $this->sync($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     * @param resource   $contents
     */
    public function writeStream($location, $contents, array $config = []): void
    {
        $this->assertIsResource($contents);
        $this->rewindStream($contents);

        $path = $this->resolvePath($location);

        $this->adapter->writeStream($path, $contents, $this->mergeConfig($config));
        $this->sync($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function read($location): string
    {
        return $this->adapter->read($this->resolvePath($location));
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function readStream($location)
    {
        return $this->adapter->readStream($this->resolvePath($location));
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function delete($location): void
    {
        $path = $this->resolvePath($location);

        $this->adapter->delete($path);
        $this->sync($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function deleteDirectory($location): void
    {
        $path = $this->resolvePath($location);

        $this->adapter->deleteDirectory($path);
        $this->sync($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function createDirectory($location, array $config = []): void
    {
        $path = $this->resolvePath($location);

        $this->adapter->createDirectory($path, $this->mergeConfig($config));
        $this->sync($path);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $location
     */
    public function listContents($location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        $recordsIterator = $this->dbafs->getRecords($this->resolvePath($location), $deep);

        return (new DirectoryListing($recordsIterator))->map(
            fn (array $record): StorageAttributes => $this->getLazyStorageAttributes($record)
        );
    }

    /**
     * @param string|int $source
     * @param string|int $destination
     *
     * {@inheritdoc}
     */
    public function move($source, $destination, array $config = []): void
    {
        $sourcePath = $this->resolvePath($source);
        $destinationPath = $this->resolvePath($destination);

        $this->adapter->move($sourcePath, $destinationPath, $this->mergeConfig($config));
        $this->sync($sourcePath, $destinationPath);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $source
     * @param string|int $destination
     */
    public function copy($source, $destination, array $config = []): void
    {
        $destinationPath = $this->resolvePath($destination);

        $this->adapter->copy($this->resolvePath($source), $destinationPath, $this->mergeConfig($config));
        $this->sync($destinationPath);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $path
     */
    public function lastModified($path): int
    {
        return $this->adapter->lastModified($this->resolvePath($path))->lastModified();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $path
     */
    public function fileSize($path): int
    {
        return $this->adapter->fileSize($this->resolvePath($path))->fileSize();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $path
     */
    public function mimeType($path): string
    {
        return $this->adapter->mimeType($this->resolvePath($path))->mimeType();
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $path
     */
    public function setVisibility($path, string $visibility): void
    {
        $this->adapter->setVisibility($this->resolvePath($path), $visibility);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $path
     */
    public function visibility($path): string
    {
        return $this->adapter->visibility($this->resolvePath($path))->visibility();
    }

    public function setExtraMetadata(): void
    {
        // todo
    }

    /**
     * @param string|int $location
     * @phpstan-return ExtraMetadata
     */
    public function extraMetadata($location): array
    {
        $record = $this->dbafs->getRecord($this->resolvePath($location));

        if (null === $record) {
            // todo: find the right exception
            throw new \RuntimeException('');
        }

        return $record['extra'];
    }

    /**
     * Synchronizes the database assisted file system. If a $scope is
     * provided only a certain file/subdirectory will be synchronized.
     *
     * @param string ...$scope relative paths inside the filesystem root
     */
    public function sync(string ...$scope): ChangeSet
    {
        return $this->dbafs->sync($this->adapter, ...$scope);
    }

    /**
     * Resolve DBAFS IDs and UUIDs to paths. If a path was given, normalize it.
     *
     * @param string|int $identifier
     */
    private function resolvePath($identifier): string
    {
        if (is_numeric($identifier)) {
            $path = $this->dbafs->getPathFromId((int) $identifier);

            if (null === $path && \is_int($identifier)) {
                // todo: find the right exception
                throw new \InvalidArgumentException("Could not resolve a filesystem path for ID $identifier.");
            }

            return $path ?? $this->pathNormalizer->normalizePath((string) $identifier);
        }

        $identifier = (string) $identifier;

        if (Validator::isBinaryUuid($identifier)) {
            return $this->dbafs->getPathFromUuid($identifier) ?? $this->pathNormalizer->normalizePath($identifier);
        }

        if (Validator::isStringUuid($identifier)) {
            return $this->dbafs->getPathFromUuid(StringUtil::uuidToBin($identifier)) ?? $this->pathNormalizer->normalizePath($identifier);
        }

        return $this->pathNormalizer->normalizePath($identifier);
    }

    private function mergeConfig(array $options): Config
    {
        return empty($options) ? $this->config : $this->config->extend($options);
    }

    /**
     * Returns a @see StorageAttributes ghost object that only has the path and
     * extra metadata set. Accessing any other property will trigger fully
     * initializing it and thus asking the underlying adapter for attributes.
     */
    private function getLazyStorageAttributes(array $record): StorageAttributes
    {
        $isFile = $record['isFile'];
        $path = $record['path'];
        $targetClass = $isFile ? FileAttributes::class : DirectoryAttributes::class;

        // Get name of private property like `get_mangled_object_vars` would return
        $privateProperty = static fn (string $className, string $property): string => "\0$className\0$property";

        // Lazy initializer for FileAttributes
        $fileAttributesInitializer = function (GhostObjectInterface $ghostObject, string $method, array $parameters, &$initializer, array &$properties) use ($privateProperty, $path) {
            $initializer = null;

            $properties[$privateProperty(FileAttributes::class, 'fileSize')] = $this->adapter->fileSize($path)->fileSize();
            $properties[$privateProperty(FileAttributes::class, 'visibility')] = $this->adapter->visibility($path)->visibility();
            $properties[$privateProperty(FileAttributes::class, 'lastModified')] = $this->adapter->lastModified($path)->lastModified();
            $properties[$privateProperty(FileAttributes::class, 'mimeType')] = $this->adapter->mimeType($path)->mimeType();

            return true;
        };

        // Lazy initializer for DirectoryAttributes
        $directoryAttributesInitializer = function (GhostObjectInterface $ghostObject, string $method, array $parameters, &$initializer, array &$properties) use ($privateProperty, $path) {
            $initializer = null;

            $properties[$privateProperty(DirectoryAttributes::class, 'visibility')] = $this->adapter->visibility($path)->visibility();
            $properties[$privateProperty(DirectoryAttributes::class, 'lastModified')] = $this->adapter->lastModified($path)->lastModified();

            return true;
        };

        /** @var GhostObjectInterface<StorageAttributes>&StorageAttributes $instance */
        $instance = $this->proxyFactory->createProxy(
            $targetClass,
            $isFile ? $fileAttributesInitializer : $directoryAttributesInitializer,
            [
                'skippedProperties' => [
                    $privateProperty($targetClass, 'path'),
                    $privateProperty($targetClass, 'extraMetadata'),
                ],
            ]
        );

        $knownProperties = [
            'path' => $path,
            'extraMetadata' => $record['extra'],
        ];

        foreach ($knownProperties as $property => $value) {
            $reflection = new \ReflectionProperty($targetClass, $property);
            $reflection->setAccessible(true);
            $reflection->setValue($instance, $value);
        }

        return $instance;
    }

    /**
     * @see \League\Flysystem\Filesystem::assertIsResource()
     *
     * @param mixed $contents
     */
    private function assertIsResource($contents): void
    {
        if (false === \is_resource($contents)) {
            throw new InvalidStreamProvided('Invalid stream provided, expected stream resource, received '.\gettype($contents));
        }

        if ('stream' !== ($type = get_resource_type($contents))) {
            throw new InvalidStreamProvided('Invalid stream provided, expected stream resource, received resource of type '.$type);
        }
    }

    /**
     * @see \League\Flysystem\Filesystem::rewindStream()
     *
     * @param resource $resource
     */
    private function rewindStream($resource): void
    {
        if (0 !== ftell($resource) && stream_get_meta_data($resource)['seekable']) {
            rewind($resource);
        }
    }
}
