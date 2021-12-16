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
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToResolveFilesystemMount;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

/**
 * A virtual filesystem is Contao's version of a MountManager. You can operate
 * on it like on any other FilesystemOperator but under the hood calls are
 * redirected to other FilesystemOperator instances depending on the path
 * prefix. A more specific prefix always wins over a more general one.
 *
 * Example:
 *   If operator A is mounted to 'files' and operator B to 'files/media',
 *   calling read('files/media/foo') will issue a call to B->read('foo')
 *   while calling fileExists('files/bar/baz') will issue a call to
 *   A->fileExists('bar/baz') instead.
 */
class VirtualFilesystem implements DbafsFilesystemOperator
{
    /**
     * @var array<string, FilesystemOperator>
     */
    private array $operators;

    /**
     * @var array<string, DbafsFilesystemOperator>
     */
    private array $dbafsOperators;

    /**
     * @param iterable<FilesystemOperator> $operators
     */
    public function __construct(iterable $operators)
    {
        $this->operators = $operators instanceof \Traversable ? iterator_to_array($operators) : $operators;
        $this->dbafsOperators = array_filter($this->operators, static fn (FilesystemOperator $operator): bool => $operator instanceof DbafsFilesystemOperator);
    }

    public function fileExists($location, int $accessType = self::SYNCED_ONLY): bool
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        // If an operator could be resolved with a UUID, we already know the target exists
        if ($resolvedLocation instanceof Uuid) {
            return true;
        }

        try {
            return $operator instanceof DbafsFilesystemOperator ?
                $operator->fileExists($resolvedLocation, $accessType) :
                $operator->fileExists($resolvedLocation);
        } catch (UnableToCheckFileExistence $e) {
            throw UnableToCheckFileExistence::forLocation((string) $location, $e);
        }
    }

    public function read($location): string
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        try {
            return $operator->read($resolvedLocation);
        } catch (UnableToReadFile $e) {
            throw UnableToReadFile::fromLocation((string) $location, $e->reason(), $e);
        }
    }

    public function readStream($location)
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        try {
            return $operator->readStream($resolvedLocation);
        } catch (UnableToReadFile $e) {
            throw UnableToReadFile::fromLocation((string) $location, $e->reason(), $e);
        }
    }

    public function listContents($location, bool $deep = self::LIST_SHALLOW, int $accessType = self::SYNCED_ONLY): DirectoryListing
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation, $resolvedPrefix] = $this->findOperatorAndLocation($location);

        $contents = $operator instanceof DbafsFilesystemOperator ?
            $operator->listContents($resolvedLocation, $deep, $accessType) :
            $operator->listContents($resolvedLocation, $deep)
        ;

        if (null === $resolvedPrefix) {
            return $contents;
        }

        return $contents->map(
            static fn (StorageAttributes $attributes): StorageAttributes => $attributes->withPath(Path::join($resolvedPrefix, $attributes->path()))
        );
    }

    public function lastModified($path, int $accessType = self::SYNCED_ONLY): int
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($path);

        try {
            return $operator instanceof DbafsFilesystemOperator ?
                $operator->lastModified($resolvedLocation, $accessType) :
                $operator->lastModified($resolvedLocation);
        } catch (UnableToRetrieveMetadata $e) {
            throw UnableToRetrieveMetadata::lastModified((string) $path, $e->reason(), $e);
        }
    }

    public function fileSize($path, int $accessType = self::SYNCED_ONLY): int
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($path);

        try {
            return $operator instanceof DbafsFilesystemOperator ?
                $operator->fileSize($resolvedLocation, $accessType) :
                $operator->fileSize($resolvedLocation);
        } catch (UnableToRetrieveMetadata $e) {
            throw UnableToRetrieveMetadata::fileSize((string) $path, $e->reason(), $e);
        }
    }

    public function mimeType($path, int $accessType = self::SYNCED_ONLY): string
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($path);

        try {
            return $operator instanceof DbafsFilesystemOperator ?
                $operator->mimeType($resolvedLocation, $accessType) :
                $operator->mimeType($resolvedLocation);
        } catch (UnableToRetrieveMetadata $e) {
            throw UnableToRetrieveMetadata::mimeType((string) $path, $e->reason(), $e);
        }
    }

    public function visibility($path): string
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($path);

        try {
            return $operator->visibility($resolvedLocation);
        } catch (UnableToRetrieveMetadata $e) {
            throw UnableToRetrieveMetadata::mimeType((string) $path, $e->reason(), $e);
        }
    }

    public function write($location, string $contents, array $config = []): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        try {
            $operator->write($resolvedLocation, $contents, $config);
        } catch (UnableToWriteFile $e) {
            throw UnableToWriteFile::atLocation((string) $location, $e->reason(), $e);
        }
    }

    public function writeStream($location, $contents, array $config = []): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        try {
            $operator->writeStream($resolvedLocation, $contents, $config);
        } catch (UnableToWriteFile $e) {
            throw UnableToWriteFile::atLocation((string) $location, $e->reason(), $e);
        }
    }

    public function setVisibility($path, string $visibility): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($path);

        $operator->setVisibility($resolvedLocation, $visibility);
    }

    public function delete($location): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        try {
            $operator->delete($resolvedLocation);
        } catch (UnableToDeleteFile $e) {
            throw UnableToDeleteFile::atLocation((string) $location, $e->reason(), $e);
        }
    }

    public function deleteDirectory($location): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        try {
            $operator->deleteDirectory($resolvedLocation);
        } catch (UnableToDeleteDirectory $e) {
            throw UnableToDeleteDirectory::atLocation((string) $location, $e->reason(), $e);
        }
    }

    public function createDirectory($location, array $config = []): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        try {
            $operator->createDirectory($resolvedLocation, $config);
        } catch (UnableToCreateDirectory $e) {
            throw UnableToCreateDirectory::dueToFailure((string) $location, $e);
        }
    }

    public function move($source, string $destination, array $config = []): void
    {
        /** @var FilesystemOperator $operatorFrom */
        [$operatorFrom, $locationFrom] = $this->findOperatorAndLocation($source);

        /** @var FilesystemOperator $operatorTo */
        /** @var string $pathTo */
        [$operatorTo, $pathTo] = $this->findOperatorAndLocation($destination);

        try {
            if ($operatorFrom === $operatorTo) {
                $operatorFrom->move($locationFrom, $pathTo);

                return;
            }

            $this->copyAcrossOperators($operatorFrom, $locationFrom, $operatorTo, $pathTo, $config);
            $operatorFrom->delete($locationFrom);
        } catch (UnableToMoveFile|UnableToReadFile|UnableToDeleteFile $e) {
            throw UnableToMoveFile::fromLocationTo((string) $source, $destination, $e);
        }
    }

    public function copy($source, string $destination, array $config = []): void
    {
        /** @var FilesystemOperator $operatorFrom */
        [$operatorFrom, $locationFrom] = $this->findOperatorAndLocation($source);

        /** @var FilesystemOperator $operatorTo */
        /** @var string $pathTo */
        [$operatorTo, $pathTo] = $this->findOperatorAndLocation($destination);

        try {
            if ($operatorFrom === $operatorTo) {
                $operatorFrom->copy($locationFrom, $pathTo);

                return;
            }

            $this->copyAcrossOperators($operatorFrom, $locationFrom, $operatorTo, $pathTo, $config);
        } catch (UnableToCopyFile|UnableToReadFile $e) {
            throw UnableToCopyFile::fromLocationTo((string) $source, $destination, $e);
        }
    }

    public function extraMetadata($location, int $accessType = self::SYNCED_ONLY): array
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        if (!$operator instanceof DbafsFilesystemOperator) {
            throw new \LogicException(sprintf('The bucket "%s" does not support retrieving extra metadata.', $this->getOperatorName($operator)));
        }

        try {
            return $operator->extraMetadata($resolvedLocation, $accessType);
        } catch (UnableToRetrieveMetadata $e) {
            throw UnableToRetrieveMetadata::create((string) $location, StorageAttributes::ATTRIBUTE_EXTRA_METADATA, $e->reason(), $e);
        }
    }

    public function setExtraMetadata($location, array $metadata): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $resolvedLocation] = $this->findOperatorAndLocation($location);

        if (!$operator instanceof DbafsFilesystemOperator) {
            throw new \LogicException(sprintf('The bucket "%s" does not support setting extra metadata.', $this->getOperatorName($operator)));
        }

        try {
            $operator->setExtraMetadata($resolvedLocation, $metadata);
        } catch (UnableToSetExtraMetadataException $e) {
            throw new UnableToSetExtraMetadataException((string) $location, $e);
        }
    }

    /**
     * @param string|Uuid $location
     * @phpstan-return array{0: FilesystemOperator, 1: string|Uuid, 2: string|null}
     *
     * @throws UnableToResolveUuidException
     */
    private function findOperatorAndLocation($location): array
    {
        if ($location instanceof Uuid) {
            foreach ($this->dbafsOperators as $dbafsOperator) {
                try {
                    if ($dbafsOperator->fileExists($location)) {
                        return [$dbafsOperator, $location, null];
                    }
                } catch (FilesystemException $e) {
                    // ignore
                }
            }

            throw new UnableToResolveUuidException($location, sprintf('Searched in DBAFS-capable mounts: "%s"', implode('", "', array_keys($this->dbafsOperators))));
        }

        $prefix = Path::canonicalize($location);

        // Find operator with the longest (= most specific) matching prefix
        while ('.' !== ($prefix = Path::getDirectory($prefix))) {
            if (null !== ($operator = $this->operators[$prefix] ?? null)) {
                return [$operator, Path::makeRelative($location, $prefix), $prefix];
            }
        }

        throw new UnableToResolveFilesystemMount(sprintf('No bucket was mounted for path "%s". Available mounts: "%s"', $location, implode('", "', array_keys($this->operators))));
    }

    private function getOperatorName(FilesystemOperator $operator): string
    {
        foreach ($this->operators as $name => $search) {
            if ($search === $operator) {
                return $name;
            }
        }

        return '';
    }

    private function copyAcrossOperators(FilesystemOperator $operatorFrom, string $pathFrom, FilesystemOperator $operatorTo, string $pathTo, array $config): void
    {
        $visibility = $config['visibility'] ?? $operatorFrom->visibility($pathFrom);

        $stream = $operatorFrom->readStream($pathFrom);
        $operatorTo->writeStream($pathTo, $stream, compact('visibility'));
    }
}
