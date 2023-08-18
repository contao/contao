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

use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Contao\CoreBundle\Filesystem\PublicUri\PublicUriProviderInterface;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemReader;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Path;

/**
 * This class allows accessing multiple Flysystem adapters as if there was only
 * one interface. Which adapter is chosen for each operation is determined by
 * the path (prefix) each adapter is registered with.
 *
 * Note: In general, user code should not directly interface with the
 *       MountManager, but use the VirtualFilesystem instead.
 *
 * @see VirtualFilesystem
 *
 * @experimental
 */
class MountManager
{
    /**
     * @var array<string|int, FilesystemAdapter>
     */
    private array $mounts = [];

    /**
     * @param iterable<int, PublicUriProviderInterface> $publicUriProviders
     */
    public function __construct(private readonly iterable $publicUriProviders = [])
    {
    }

    public function mount(FilesystemAdapter $adapter, string $path = ''): self
    {
        $this->mounts[$path] = $adapter;

        krsort($this->mounts, SORT_NATURAL);

        return $this;
    }

    /**
     * @return array<string|int, FilesystemAdapter>
     */
    public function getMounts(): array
    {
        return $this->mounts;
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function fileExists(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        try {
            [$adapter, $adapterPath] = $this->getAdapterAndPath($path);
        } catch (\RuntimeException) {
            // Tolerate non-existing mount-points
            return false;
        }

        try {
            return $adapter->fileExists($adapterPath);
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToCheckIfFileExists($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function directoryExists(string $path): bool
    {
        if ('' === $path) {
            return false;
        }

        try {
            [$adapter, $adapterPath] = $this->getAdapterAndPath($path);
        } catch (\RuntimeException) {
            // Tolerate non-existing mount-points
            return false;
        }

        try {
            return $adapter->directoryExists($adapterPath);
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToCheckIfDirectoryExists($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function read(string $path): string
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            return $adapter->read($adapterPath);
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToRead($path, $e);
        }
    }

    /**
     * @return resource
     *
     * @throws VirtualFilesystemException
     */
    public function readStream(string $path)
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            return $adapter->readStream($adapterPath);
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToRead($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function write(string $path, string $contents, array $options = []): void
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            $adapter->write($adapterPath, $contents, new Config($options));
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToWrite($path, $e);
        }
    }

    /**
     * @param resource $contents
     *
     * @throws VirtualFilesystemException
     */
    public function writeStream(string $path, $contents, array $options = []): void
    {
        FilesystemUtil::assertIsResource($contents);
        FilesystemUtil::rewindStream($contents);

        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            $adapter->writeStream($adapterPath, $contents, new Config($options));
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToWrite($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function delete(string $path): void
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            $adapter->delete($adapterPath);
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToDelete($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function deleteDirectory(string $path): void
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            $adapter->deleteDirectory($adapterPath);
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToDeleteDirectory($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function createDirectory(string $path, array $options = []): void
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            $adapter->createDirectory($adapterPath, new Config($options));
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToCreateDirectory($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function copy(string $pathFrom, string $pathTo, array $options = []): void
    {
        [$adapterFrom, $adapterPathFrom] = $this->getAdapterAndPath($pathFrom);
        [$adapterTo, $adapterPathTo] = $this->getAdapterAndPath($pathTo);

        try {
            if ($adapterFrom === $adapterTo) {
                $adapterFrom->copy($adapterPathFrom, $adapterPathTo, new Config($options));

                return;
            }

            $visibility = $options['visibility'] ?? $adapterFrom->visibility($adapterPathFrom)->visibility();

            $stream = $adapterFrom->readStream($adapterPathFrom);
            $adapterTo->writeStream($adapterPathTo, $stream, new Config(['visibility' => $visibility]));
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToCopy($pathFrom, $pathTo, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function move(string $pathFrom, string $pathTo, array $options = []): void
    {
        [$adapterFrom, $adapterPathFrom] = $this->getAdapterAndPath($pathFrom);
        [$adapterTo, $adapterPathTo] = $this->getAdapterAndPath($pathTo);

        try {
            if ($adapterFrom === $adapterTo) {
                $adapterFrom->move($adapterPathFrom, $adapterPathTo, new Config($options));

                return;
            }

            $visibility = $options['visibility'] ?? $adapterFrom->visibility($adapterPathFrom)->visibility();

            $stream = $adapterFrom->readStream($adapterPathFrom);
            $adapterTo->writeStream($adapterPathTo, $stream, new Config(['visibility' => $visibility]));

            $adapterFrom->delete($adapterPathFrom);
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToMove($pathFrom, $pathTo, $e);
        }
    }

    /**
     * @return \Generator<FilesystemItem>
     *
     * @throws VirtualFilesystemException
     */
    public function listContents(string $path, bool $deep = false): \Generator
    {
        $path = Path::canonicalize($path);
        $virtualPathsSet = [];

        foreach (array_keys($this->mounts) as $mountPath) {
            $relativeSearchPath = Path::makeRelative((string) $mountPath, $path);

            if ('' === $relativeSearchPath || str_starts_with($relativeSearchPath, '..')) {
                continue;
            }

            if (!$deep && mb_substr_count($relativeSearchPath, '/') > 0) {
                continue;
            }

            $virtualPathsSet[$mountPath] = true;
        }

        ksort($virtualPathsSet, SORT_NATURAL);

        // Yield items and track all traversed directories
        foreach ($this->doListContents($path, $deep) as $item) {
            if (!$item->isFile()) {
                unset($virtualPathsSet[$item->getPath()]);
            }

            yield $item;
        }

        // Yield remaining virtual paths
        foreach (array_keys($virtualPathsSet) as $virtualPath) {
            yield new FilesystemItem(false, (string) $virtualPath);

            if ($deep) {
                yield from $this->doListContents((string) $virtualPath, true);
            }
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function getLastModified(string $path): int
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            return $adapter->lastModified($adapterPath)->lastModified();
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToRetrieveMetadata($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function getFileSize(string $path): int
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            return $adapter->fileSize($adapterPath)->fileSize();
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToRetrieveMetadata($path, $e);
        }
    }

    /**
     * @throws VirtualFilesystemException
     */
    public function getMimeType(string $path): string
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        try {
            return $adapter->mimeType($adapterPath)->mimeType();
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToRetrieveMetadata($path, $e);
        }
    }

    public function generatePublicUri(string $path, OptionsInterface|null $options = null): UriInterface|null
    {
        [$adapter, $adapterPath] = $this->getAdapterAndPath($path);

        foreach ($this->publicUriProviders as $provider) {
            $uri = $provider->getUri($adapter, $adapterPath, $options);

            if ($uri instanceof UriInterface) {
                return $uri;
            }
        }

        return null;
    }

    /**
     * @return array{0: FilesystemAdapter, 1: string, 2: string}
     */
    private function getAdapterAndPath(string $path): array
    {
        if ('' !== $path) {
            $prefix = $path;

            // Find the adapter with the longest (= most specific) matching prefix
            do {
                $adapter = $this->mounts[$prefix] ?? null;

                if ($adapter instanceof FilesystemAdapter) {
                    return [$adapter, Path::makeRelative($path, $prefix), $prefix];
                }
            } while ('.' !== ($prefix = \dirname($prefix)));
        }

        // Root adapter
        $adapter = $this->mounts[''] ?? null;

        if ($adapter instanceof FilesystemAdapter) {
            return [$adapter, $path, ''];
        }

        throw new \RuntimeException(sprintf('No adapter was mounted to serve path "%s".', $path));
    }

    /**
     * @return \Generator<FilesystemItem>
     */
    private function doListContents(string $path, bool $deep): \Generator
    {
        [$adapter, $adapterPath, $prefix] = $this->getAdapterAndPath($path);

        try {
            // If $deep is true we shallow-read directories recursively, because
            // there could be another adapter mounted further down in the tree.
            foreach ($adapter->listContents($adapterPath, FilesystemReader::LIST_SHALLOW) as $flysystemItem) {
                $item = FilesystemItem::fromStorageAttributes($flysystemItem, $prefix);
                $itemPath = $item->getPath();

                if ($item->isFile()) {
                    yield $item->withMetadataIfNotDefined(
                        fn () => $this->getLastModified($itemPath),
                        fn () => $this->getFileSize($itemPath),
                        fn () => $this->getMimeType($itemPath),
                    );

                    continue;
                }

                yield $item;

                if ($deep) {
                    yield from $this->doListContents(Path::join($prefix, $flysystemItem->path()), true);
                }
            }
        } catch (FilesystemException $e) {
            throw VirtualFilesystemException::unableToListContents($path, $e);
        }
    }
}
