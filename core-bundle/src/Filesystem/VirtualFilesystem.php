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

use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

/**
 * Use the VirtualFilesystem to access resources from mounted adapters and
 * registered DBAFS instances. The class can be instantiated with a path
 * prefix (e.g. "assets/images") to get a different root and/or as a readonly
 * view to prevent accidental mutations.
 *
 * In each method you can either pass in a path (string) or a @see Uuid to
 * target resources. For operations that can be short-circuited via a DBAFS,
 * you can optionally set access flags to bypass the DBAFS or to force a
 * (partial) synchronization beforehand.
 *
 * @experimental
 */
class VirtualFilesystem implements VirtualFilesystemInterface
{
    private MountManager $mountManager;
    private DbafsManager $dbafsManager;
    private string $prefix;
    private bool $readonly;

    /**
     * @internal Use the "contao.filesystem.virtual_factory" service to create new instances.
     */
    public function __construct(MountManager $mountManager, DbafsManager $dbafsManager, string $prefix = '', bool $readonly = false)
    {
        $this->mountManager = $mountManager;
        $this->dbafsManager = $dbafsManager;
        $this->prefix = $prefix;
        $this->readonly = $readonly;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function isReadOnly(): bool
    {
        return $this->readonly;
    }

    public function has($location, int $accessFlags = self::NONE): bool
    {
        return $this->checkResourceExists($location, $accessFlags, 'has');
    }

    public function fileExists($location, int $accessFlags = self::NONE): bool
    {
        return $this->checkResourceExists($location, $accessFlags, 'fileExists');
    }

    public function directoryExists($location, int $accessFlags = self::NONE): bool
    {
        return $this->checkResourceExists($location, $accessFlags, 'directoryExists');
    }

    public function read($location): string
    {
        return $this->mountManager->read($this->resolve($location));
    }

    public function readStream($location)
    {
        return $this->mountManager->readStream($this->resolve($location));
    }

    public function write($location, string $contents, array $options = []): void
    {
        $this->ensureNotReadonly();

        $path = $this->resolve($location);

        $this->mountManager->write($path, $contents, $options);
        $this->dbafsManager->sync($path);
    }

    public function writeStream($location, $contents, array $options = []): void
    {
        $this->ensureNotReadonly();

        FilesystemUtil::assertIsResource($contents);

        $path = $this->resolve($location);

        $this->mountManager->writeStream($path, $contents, $options);
        $this->dbafsManager->sync($path);
    }

    public function delete($location): void
    {
        $this->ensureNotReadonly();

        $path = $this->resolve($location);

        $this->mountManager->delete($path);
        $this->dbafsManager->sync($path);
    }

    public function deleteDirectory($location): void
    {
        $this->ensureNotReadonly();

        $path = $this->resolve($location);

        $this->mountManager->deleteDirectory($path);
        $this->dbafsManager->sync($path);
    }

    public function createDirectory($location, array $options = []): void
    {
        $this->ensureNotReadonly();

        $path = $this->resolve($location);

        $this->mountManager->createDirectory($path, $options);
        $this->dbafsManager->sync($path);
    }

    public function copy($source, string $destination, array $options = []): void
    {
        $this->ensureNotReadonly();

        $pathFrom = $this->resolve($source);
        $pathTo = $this->resolve($destination);

        $this->mountManager->copy($pathFrom, $pathTo, $options);
        $this->dbafsManager->sync($pathFrom, $pathTo);
    }

    public function move($source, string $destination, array $options = []): void
    {
        $this->ensureNotReadonly();

        $pathFrom = $this->resolve($source);
        $pathTo = $this->resolve($destination);

        $this->mountManager->move($pathFrom, $pathTo, $options);
        $this->dbafsManager->sync($pathFrom, $pathTo);
    }

    public function get($location, int $accessFlags = self::NONE): ?FilesystemItem
    {
        $path = $this->resolve($location);
        $relativePath = Path::makeRelative($path, $this->prefix);

        if ($accessFlags & self::FORCE_SYNC) {
            $this->dbafsManager->sync($path);
            $accessFlags &= ~self::FORCE_SYNC;
        }

        if ($this->fileExists($relativePath, $accessFlags)) {
            return new FilesystemItem(
                true,
                $relativePath,
                fn () => $this->getLastModified($relativePath, $accessFlags),
                fn () => $this->getFileSize($relativePath, $accessFlags),
                fn () => $this->getMimeType($relativePath, $accessFlags),
                fn () => $this->getExtraMetadata($relativePath, $accessFlags),
            );
        }

        if ($this->directoryExists($relativePath, $accessFlags)) {
            return new FilesystemItem(false, $relativePath);
        }

        return null;
    }

    public function listContents($location, bool $deep = false, int $accessFlags = self::NONE): FilesystemItemIterator
    {
        $path = $this->resolve($location);

        if ($accessFlags & self::FORCE_SYNC) {
            $this->dbafsManager->sync($path);
        }

        return new FilesystemItemIterator($this->doListContents($path, $deep, $accessFlags));
    }

    public function getLastModified($location, int $accessFlags = self::NONE): int
    {
        $path = $this->resolve($location);

        if ($accessFlags & self::FORCE_SYNC) {
            $this->dbafsManager->sync($path);
        }

        if (!($accessFlags & self::BYPASS_DBAFS) && null !== ($lastModified = $this->dbafsManager->getLastModified($path))) {
            return $lastModified;
        }

        return $this->mountManager->getLastModified($path);
    }

    public function getFileSize($location, int $accessFlags = self::NONE): int
    {
        $path = $this->resolve($location);

        if ($accessFlags & self::FORCE_SYNC) {
            $this->dbafsManager->sync($path);
        }

        if (!($accessFlags & self::BYPASS_DBAFS) && null !== ($fileSize = $this->dbafsManager->getFileSize($path))) {
            return $fileSize;
        }

        return $this->mountManager->getFileSize($path);
    }

    public function getMimeType($location, int $accessFlags = self::NONE): string
    {
        $path = $this->resolve($location);

        if ($accessFlags & self::FORCE_SYNC) {
            $this->dbafsManager->sync($path);
        }

        if (!($accessFlags & self::BYPASS_DBAFS) && null !== ($mimeType = $this->dbafsManager->getMimeType($path))) {
            return $mimeType;
        }

        return $this->mountManager->getMimeType($path);
    }

    public function getExtraMetadata($location, int $accessFlags = self::NONE): array
    {
        $path = $this->resolve($location);

        if ($accessFlags & self::FORCE_SYNC) {
            $this->dbafsManager->sync($path);
        }

        if ($accessFlags & self::BYPASS_DBAFS) {
            return [];
        }

        return $this->dbafsManager->getExtraMetadata($path);
    }

    public function setExtraMetadata($location, array $metadata): void
    {
        $this->ensureNotReadonly();

        $this->dbafsManager->setExtraMetadata($this->resolve($location), $metadata);
    }

    public function generatePublicUri($location, OptionsInterface $options = null): ?UriInterface
    {
        $path = $this->resolve($location);

        return $this->mountManager->generatePublicUri($path, $options);
    }

    /**
     * @param string|Uuid                          $location
     * @param 'fileExists'|'directoryExists'|'has' $method
     *
     * @throws VirtualFilesystemException
     */
    private function checkResourceExists($location, int $accessFlags, string $method): bool
    {
        if ($location instanceof Uuid) {
            if ($accessFlags & self::BYPASS_DBAFS) {
                throw new \LogicException('Cannot use a UUID in combination with VirtualFilesystem::BYPASS_DBAFS to check if a resource exists.');
            }

            try {
                $this->dbafsManager->resolveUuid($location, $this->prefix);
            } catch (UnableToResolveUuidException $e) {
                return false;
            }

            // Do not care about VirtualFilesystem::FORCE_SYNC at this point as
            // the resource was already found.

            return true;
        }

        $path = $this->resolve($location);

        if ($accessFlags & self::FORCE_SYNC) {
            $this->dbafsManager->sync($path);
        }

        if (!($accessFlags & self::BYPASS_DBAFS) && $this->dbafsManager->match($path)) {
            return $this->dbafsManager->$method($path);
        }

        return 'has' === $method
            ? $this->mountManager->fileExists($path) || $this->mountManager->directoryExists($path)
            : $this->mountManager->$method($path);
    }

    /**
     * @return \Generator<FilesystemItem>
     */
    private function doListContents(string $path, bool $deep, int $accessFlags): \Generator
    {
        // Read from DBAFS but enhance result with file metadata on demand
        if (!($accessFlags & self::BYPASS_DBAFS) && $this->dbafsManager->match($path)) {
            /** @var FilesystemItem $item */
            foreach ($this->dbafsManager->listContents($path, $deep) as $item) {
                $path = $item->getPath();
                $item = $item->withPath(Path::makeRelative($path, $this->prefix));

                if (!$item->isFile()) {
                    yield $item;

                    continue;
                }

                yield $item->withMetadataIfNotDefined(
                    fn () => $this->mountManager->getLastModified($path),
                    fn () => $this->mountManager->getFileSize($path),
                    fn () => $this->mountManager->getMimeType($path),
                );
            }

            return;
        }

        // Read from adapter, but enhance result with extra metadata on demand
        /** @var FilesystemItem $item */
        foreach ($this->mountManager->listContents($path, $deep) as $item) {
            $path = $item->getPath();

            yield $item
                ->withPath(Path::makeRelative($path, $this->prefix))
                ->withExtraMetadata(fn () => $this->dbafsManager->getExtraMetadata($path))
            ;
        }
    }

    /**
     * @param string|Uuid $location
     */
    private function resolve($location): string
    {
        $path = $location instanceof Uuid ?
            Path::canonicalize($this->dbafsManager->resolveUuid($location, $this->prefix)) :
            Path::canonicalize($location)
        ;

        if (Path::isAbsolute($path)) {
            throw new \OutOfBoundsException(sprintf('Virtual filesystem path "%s" cannot be absolute.', $path));
        }

        if (str_starts_with($path, '..')) {
            throw new \OutOfBoundsException(sprintf('Virtual filesystem path "%s" must not escape the filesystem boundary.', $path));
        }

        return Path::join($this->prefix, $path);
    }

    private function ensureNotReadonly(): void
    {
        if ($this->readonly) {
            throw new \LogicException('Tried to mutate a readonly filesystem instance.');
        }
    }
}
