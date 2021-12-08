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
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToResolveFilesystemMount;
use Symfony\Component\Filesystem\Path;

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
 *
 * todo: handle and rethrow exceptions
 */
class VirtualFilesystem implements FilesystemOperator
{
    /**
     * @var array<string, FilesystemOperator>
     */
    private array $operators;

    /**
     * @param iterable<FilesystemOperator> $operators
     */
    public function __construct(iterable $operators)
    {
        $this->operators = $operators instanceof \Traversable ? iterator_to_array($operators) : $operators;
    }

    public function fileExists(string $location): bool
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        return $operator->fileExists($path);
    }

    public function read(string $location): string
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        return $operator->read($path);
    }

    public function readStream(string $location)
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        return $operator->readStream($path);
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path, $prefix] = $this->findOperatorAndPath($location);

        return $operator
            ->listContents($path)
            ->map(static fn (StorageAttributes $attributes): StorageAttributes => $attributes->withPath(Path::join($prefix, $attributes->path())))
        ;
    }

    public function lastModified(string $path): int
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($path);

        return $operator->lastModified($path);
    }

    public function fileSize(string $path): int
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($path);

        return $operator->fileSize($path);
    }

    public function mimeType(string $path): string
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($path);

        return $operator->mimeType($path);
    }

    public function visibility(string $path): string
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($path);

        return $operator->visibility($path);
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        $operator->write($path, $contents, $config);
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        $operator->writeStream($path, $contents, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($path);

        $operator->setVisibility($path, $visibility);
    }

    public function delete(string $location): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        $operator->delete($path);
    }

    public function deleteDirectory(string $location): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        $operator->deleteDirectory($path);
    }

    public function createDirectory(string $location, array $config = []): void
    {
        /** @var FilesystemOperator $operator */
        [$operator, $path] = $this->findOperatorAndPath($location);

        $operator->createDirectory($path, $config);
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        /** @var FilesystemOperator $operatorFrom */
        [$operatorFrom, $pathFrom] = $this->findOperatorAndPath($source);

        /** @var FilesystemOperator $operatorTo */
        [$operatorTo, $pathTo] = $this->findOperatorAndPath($destination);

        if ($operatorFrom === $operatorTo) {
            $operatorFrom->move($pathFrom, $pathTo);

            return;
        }

        $this->copyAcrossOperators($operatorFrom, $pathFrom, $operatorTo, $pathTo, $config);
        $operatorFrom->delete($pathFrom);
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        /** @var FilesystemOperator $operatorFrom */
        [$operatorFrom, $pathFrom] = $this->findOperatorAndPath($source);

        /** @var FilesystemOperator $operatorTo */
        [$operatorTo, $pathTo] = $this->findOperatorAndPath($destination);

        if ($operatorFrom === $operatorTo) {
            $operatorFrom->copy($pathFrom, $pathTo);

            return;
        }

        $this->copyAcrossOperators($operatorFrom, $pathFrom, $operatorTo, $pathTo, $config);
    }

    /**
     * @phpstan-return array{0: FilesystemOperator, 1: string, 2: string}
     */
    private function findOperatorAndPath(string $path): array
    {
        $prefix = Path::canonicalize($path);

        // Find operator with the longest (= most specific) matching prefix
        while ('.' !== ($prefix = Path::getDirectory($prefix))) {
            if (null !== ($operator = $this->operators[$prefix] ?? null)) {
                return [$operator, Path::makeRelative($path, $prefix), $prefix];
            }
        }

        throw new UnableToResolveFilesystemMount(sprintf('No operator was mounted for path "%s". Available mounts: "%s"', $path, implode('", "', array_keys($this->operators))));
    }

    private function copyAcrossOperators(FilesystemOperator $operatorFrom, string $pathFrom, FilesystemOperator $operatorTo, string $pathTo, array $config): void
    {
        $visibility = $config['visibility'] ?? $operatorFrom->visibility($pathFrom);

        $stream = $operatorFrom->readStream($pathFrom);
        $operatorTo->writeStream($pathTo, $stream, compact('visibility'));
    }
}
