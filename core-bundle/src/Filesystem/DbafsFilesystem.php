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

use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\DirectoryListing;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InvalidStreamProvided;
use League\Flysystem\PathNormalizer;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\WhitespacePathNormalizer;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Proxy\GhostObjectInterface;
use Symfony\Component\Uid\Uuid;

/**
 * This Flysystem operator wraps a DBAFS layer around the underlying filesystem
 * adapter: Listing content will only access the database, operations that can
 * mutate the filesystem, will automatically trigger a synchronization.
 *
 * Additionally, there is support to directly retrieve the 'extra metadata'
 * from a file's / directory's StorageAttributes via a dedicated method.
 *
 * We use the widened DbafsFilesystemOperator interface - instead of calling
 * methods with resource paths (string), you can also pass in a UUID of the
 * encapsulated DbafsInterface.
 */
final class DbafsFilesystem implements DbafsFilesystemOperator
{
    private FilesystemAdapter $adapter;
    private Config $config;
    private PathNormalizer $pathNormalizer;
    private DbafsInterface $dbafs;
    private LazyLoadingGhostFactory $proxyFactory;

    public function __construct(DbafsInterface $dbafs, FilesystemAdapter $adapter, array $config = [], LazyLoadingGhostFactory $proxyFactory = null, PathNormalizer $pathNormalizer = null)
    {
        $this->dbafs = $dbafs;
        $this->adapter = $adapter;
        $this->proxyFactory = $proxyFactory ?? new LazyLoadingGhostFactory();
        $this->config = new Config($config);
        $this->pathNormalizer = $pathNormalizer ?? new WhitespacePathNormalizer();
    }

    public function fileExists($location, int $accessType = self::SYNCED_ONLY): bool
    {
        $shouldBypass = self::BYPASS_DBAFS === $accessType;

        if ($location instanceof Uuid && $shouldBypass) {
            throw new \LogicException('Cannot use a UUID in combination with DbafsFilesystemOperator::BYPASS_DBAFS to check if a file exists.');
        }

        try {
            $normalizedPath = $this->normalizePath($location);
        } catch (UnableToResolveUuidException $e) {
            return false;
        }

        if ($shouldBypass) {
            return $this->adapter->fileExists($normalizedPath);
        }

        return null !== $this->dbafs->getRecord($normalizedPath);
    }

    public function read($location): string
    {
        return $this->adapter->read($this->normalizePath($location));
    }

    public function readStream($location)
    {
        return $this->adapter->readStream($this->normalizePath($location));
    }

    public function listContents($location, bool $deep = self::LIST_SHALLOW, int $accessType = self::SYNCED_ONLY): DirectoryListing
    {
        $normalizedPath = $this->normalizePath($location);

        if (self::BYPASS_DBAFS === $accessType) {
            return $this->adapter->listContents($normalizedPath, $deep);
        }

        if (self::FORCE_SYNC === $accessType) {
            $this->sync("$normalizedPath/*");
        }

        $recordsIterator = $this->dbafs->getRecords($normalizedPath, $deep);

        return (new DirectoryListing($recordsIterator))->map(
            fn (array $record): StorageAttributes => $this->getLazyStorageAttributes($record)
        );
    }

    public function lastModified($path, int $accessType = self::SYNCED_ONLY): int
    {
        $normalizedPath = $this->normalizePath($path);

        if (self::BYPASS_DBAFS !== $accessType && $this->dbafs->supportsLastModified()) {
            if (self::FORCE_SYNC === $accessType) {
                $this->sync($path);
            }

            if (null === ($record = $this->dbafs->getRecord($normalizedPath))) {
                throw UnableToRetrieveMetadata::lastModified($normalizedPath, 'Resource does not exist in DBAFS.');
            }

            if (!\array_key_exists('lastModified', $record)) {
                throw new \LogicException(sprintf('The DBAFS class "%s" supports "lastModified" but did not set it in the record.', \get_class($this->dbafs)));
            }

            if (null !== ($lastModified = $record['lastModified'])) {
                return $lastModified;
            }
        }

        return $this->adapter->lastModified($normalizedPath)->lastModified();
    }

    public function fileSize($path, int $accessType = self::SYNCED_ONLY): int
    {
        $normalizedPath = $this->normalizePath($path);

        if (self::BYPASS_DBAFS !== $accessType && $this->dbafs->supportsFileSize()) {
            if (self::FORCE_SYNC === $accessType) {
                $this->sync($path);
            }

            if (null === ($record = $this->dbafs->getRecord($normalizedPath))) {
                throw UnableToRetrieveMetadata::fileSize($normalizedPath, 'Resource does not exist in DBAFS.');
            }

            if (!\array_key_exists('fileSize', $record)) {
                throw new \LogicException(sprintf('The DBAFS class "%s" supports "fileSize" but did not set it in the record.', \get_class($this->dbafs)));
            }

            if (null !== ($fileSize = $record['fileSize'])) {
                return $fileSize;
            }
        }

        return $this->adapter->fileSize($normalizedPath)->fileSize();
    }

    public function mimeType($path, int $accessType = self::SYNCED_ONLY): string
    {
        $normalizedPath = $this->normalizePath($path);

        if (self::BYPASS_DBAFS !== $accessType && $this->dbafs->supportsMimeType()) {
            if (self::FORCE_SYNC === $accessType) {
                $this->sync($path);
            }

            if (null === ($record = $this->dbafs->getRecord($normalizedPath))) {
                throw UnableToRetrieveMetadata::mimeType($normalizedPath, 'Resource does not exist in DBAFS.');
            }

            if (!\array_key_exists('mimeType', $record)) {
                throw new \LogicException(sprintf('The DBAFS class "%s" supports "mimeType" but did not set it in the record.', \get_class($this->dbafs)));
            }

            if (null !== ($mimeType = $record['mimeType'])) {
                return $mimeType;
            }
        }

        return $this->adapter->mimeType($normalizedPath)->mimeType();
    }

    public function visibility($path): string
    {
        return $this->adapter->visibility($this->normalizePath($path))->visibility();
    }

    public function write($location, string $contents, array $config = []): void
    {
        $normalizedPath = $this->normalizePath($location);

        $this->adapter->write($normalizedPath, $contents, $this->mergeConfig($config));
        $this->sync($normalizedPath);
    }

    public function writeStream($location, $contents, array $config = []): void
    {
        $this->assertIsResource($contents);
        $this->rewindStream($contents);

        $normalizedPath = $this->normalizePath($location);

        $this->adapter->writeStream($normalizedPath, $contents, $this->mergeConfig($config));
        $this->sync($normalizedPath);
    }

    public function setVisibility($path, string $visibility): void
    {
        $this->adapter->setVisibility($this->normalizePath($path), $visibility);
    }

    public function delete($location): void
    {
        $normalizedPath = $this->normalizePath($location);

        $this->adapter->delete($normalizedPath);
        $this->sync($normalizedPath);
    }

    public function deleteDirectory($location): void
    {
        $normalizedPath = $this->normalizePath($location);

        $this->adapter->deleteDirectory($normalizedPath);
        $this->sync($normalizedPath);
    }

    public function createDirectory($location, array $config = []): void
    {
        $normalizedPath = $this->normalizePath($location);

        $this->adapter->createDirectory($normalizedPath, $this->mergeConfig($config));
        $this->sync($normalizedPath);
    }

    public function move($source, string $destination, array $config = []): void
    {
        $sourcePath = $this->normalizePath($source);
        $destinationPath = $this->normalizePath($destination);

        $this->adapter->move($sourcePath, $destinationPath, $this->mergeConfig($config));
        $this->sync($sourcePath, $destinationPath);
    }

    public function copy($source, string $destination, array $config = []): void
    {
        $destinationPath = $this->normalizePath($destination);

        $this->adapter->copy($this->normalizePath($source), $destinationPath, $this->mergeConfig($config));
        $this->sync($destinationPath);
    }

    public function setExtraMetadata($location, array $metadata): void
    {
        $normalizedPath = $this->normalizePath($location);

        try {
            $this->dbafs->setExtraMetadata($normalizedPath, $metadata);
        } catch (\InvalidArgumentException $e) {
            throw new UnableToSetExtraMetadataException((string) $location, $e);
        }
    }

    public function extraMetadata($location, int $accessType = self::SYNCED_ONLY): array
    {
        $normalizedPath = $this->normalizePath($location);

        if (self::BYPASS_DBAFS === $accessType) {
            throw new \LogicException('Cannot get extra metadata from DBAFS with DbafsFilesystemOperator::BYPASS_DBAFS.');
        }

        if (self::FORCE_SYNC === $accessType) {
            $this->sync($location);
        }

        $record = $this->dbafs->getRecord($normalizedPath);

        if (null === $record) {
            throw UnableToRetrieveMetadata::create($normalizedPath, StorageAttributes::ATTRIBUTE_EXTRA_METADATA, 'Record does not exist in DBAFS.');
        }

        return $record['extra'];
    }

    /**
     * Synchronizes the database assisted file system. If $scope paths
     * are provided only certain files/subdirectories will be synchronized.
     *
     * Paths can have the following forms:
     *
     *   'foo/bar/baz' = just the single the file/directory foo/bar/baz
     *   'foo/**' = foo and all resources in all subdirectories
     *   'foo/*' = foo and only direct child resources of foo
     *
     * @param string ...$scope relative paths inside the filesystem root
     */
    public function sync(string ...$scope): ChangeSet
    {
        return $this->dbafs->sync($this->adapter, ...$scope);
    }

    /**
     * Resolve DBAFS UUIDs to paths. If a path was given, normalize it.
     *
     * @param string|Uuid $identifier
     *
     * @throws UnableToResolveUuidException if a provided UUID could not be converted to a path
     */
    private function normalizePath($identifier): string
    {
        if ($identifier instanceof Uuid) {
            $path = $this->dbafs->getPathFromUuid($identifier);

            if (null === $path) {
                throw new UnableToResolveUuidException($identifier);
            }

            return $path;
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
