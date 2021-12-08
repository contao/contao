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
        // TODO: Implement fileExists() method.
    }

    public function read(string $location): string
    {
        // TODO: Implement read() method.
    }

    public function readStream(string $location): void
    {
        // TODO: Implement readStream() method.
    }

    public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
    {
        // TODO: Implement listContents() method.
    }

    public function lastModified(string $path): int
    {
        // TODO: Implement lastModified() method.
    }

    public function fileSize(string $path): int
    {
        // TODO: Implement fileSize() method.
    }

    public function mimeType(string $path): string
    {
        // TODO: Implement mimeType() method.
    }

    public function visibility(string $path): string
    {
        // TODO: Implement visibility() method.
    }

    public function write(string $location, string $contents, array $config = []): void
    {
        // TODO: Implement write() method.
    }

    public function writeStream(string $location, $contents, array $config = []): void
    {
        // TODO: Implement writeStream() method.
    }

    public function setVisibility(string $path, string $visibility): void
    {
        // TODO: Implement setVisibility() method.
    }

    public function delete(string $location): void
    {
        // TODO: Implement delete() method.
    }

    public function deleteDirectory(string $location): void
    {
        // TODO: Implement deleteDirectory() method.
    }

    public function createDirectory(string $location, array $config = []): void
    {
        // TODO: Implement createDirectory() method.
    }

    public function move(string $source, string $destination, array $config = []): void
    {
        // TODO: Implement move() method.
    }

    public function copy(string $source, string $destination, array $config = []): void
    {
        // TODO: Implement copy() method.
    }

    private function findOperator(string $path): ?FilesystemOperator
    {
        $search = $path;

        // Find operator with the longest (= most specific) matching prefix
        while ('.' !== ($search = Path::getDirectory($search))) {
            if (null !== ($operator = $this->operators[$search] ?? null)) {
                return $operator;
            }
        }

        return null;
    }
}
