<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

/**
 * This decorator implements a view where only certain directories - including
 * their root trails - are visible/accessible.
 *
 * @internal
 */
class DirectoryFilterVirtualFilesystem implements VirtualFilesystemInterface
{
    /**
     * @phpstan-use VirtualFilesystemDecoratorTrait<VirtualFilesystemInterface>
     */
    use VirtualFilesystemDecoratorTrait;

    /**
     * @var list<string>
     */
    private readonly array $prefixPaths;

    /**
     * @var list<string>
     */
    private readonly array $trailPaths;

    /**
     * @param list<string> $allowedDirectories
     */
    public function __construct(VirtualFilesystemInterface $virtualFilesystem, array $allowedDirectories)
    {
        $this->inner = $virtualFilesystem;

        // Normalize
        $allowedDirectories = array_map(static fn (string $path): string => Path::canonicalize($path), $allowedDirectories);
        sort($allowedDirectories);

        $prefixPaths = [];
        $trailPaths = [''];

        $previous = null;

        foreach ($allowedDirectories as $current) {
            if (null === $previous || !Path::isBasePath($previous, $current)) {
                $prefixPaths[] = $previous = $trailPath = $current;

                while ('' !== ($trailPath = Path::getDirectory($trailPath)) && !\in_array($trailPath, $trailPaths, true)) {
                    $trailPaths[] = $trailPath;
                }
            }
        }

        $this->prefixPaths = $prefixPaths;
        $this->trailPaths = $trailPaths;
    }

    public function has(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        $path = $this->resolveLocation($location);

        return $this->fileExists($path, $accessFlags) || $this->directoryExists($path, $accessFlags);
    }

    public function fileExists(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location), true)) {
            return false;
        }

        return $this->inner->fileExists($path, $accessFlags);
    }

    public function directoryExists(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        $path = $this->resolveLocation($location);

        if ($this->isTrailPath($path) || $this->isAllowedPath($path)) {
            return true;
        }

        if (!$this->isAccessible($path, true)) {
            return false;
        }

        return $this->inner->directoryExists($path, $accessFlags);
    }

    public function read(Uuid|string $location): string
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location))) {
            throw VirtualFilesystemException::unableToRead($path);
        }

        return $this->inner->read($path);
    }

    public function readStream(Uuid|string $location)
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location))) {
            throw VirtualFilesystemException::unableToRead($path);
        }

        return $this->inner->readStream($path);
    }

    public function write(Uuid|string $location, string $contents, array $options = []): void
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location))) {
            throw VirtualFilesystemException::unableToWrite($path);
        }

        $this->inner->write($path, $contents, $options);
    }

    public function writeStream(Uuid|string $location, $contents, array $options = []): void
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location))) {
            throw VirtualFilesystemException::unableToWrite($path);
        }

        $this->inner->writeStream($path, $contents, $options);
    }

    public function delete(Uuid|string $location): void
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location))) {
            throw VirtualFilesystemException::unableToDelete($path);
        }

        $this->inner->delete($path);
    }

    public function deleteDirectory(Uuid|string $location): void
    {
        $path = $this->resolveLocation($location);

        if (!$this->isAccessible($path, true) || $this->isAllowedPath($location)) {
            throw VirtualFilesystemException::unableToDeleteDirectory($path);
        }

        $this->inner->delete($path);
    }

    public function createDirectory(Uuid|string $location, array $options = []): void
    {
        $path = $this->resolveLocation($location);

        if (!$this->isAccessible($path, true) || $this->isAllowedPath($location)) {
            throw VirtualFilesystemException::unableToCreateDirectory($path);
        }

        $this->inner->createDirectory($path, $options);
    }

    public function copy(Uuid|string $source, string $destination, array $options = []): void
    {
        $sourcePath = $this->resolveLocation($source);
        $destinationPath = $this->resolveLocation($destination);

        if (!$this->isAccessible($sourcePath) || !$this->isAccessible($destinationPath, true)) {
            throw VirtualFilesystemException::unableToCopy($sourcePath, $destinationPath);
        }

        $this->inner->copy($sourcePath, $destinationPath, $options);
    }

    public function move(Uuid|string $source, string $destination, array $options = []): void
    {
        $sourcePath = $this->resolveLocation($source);
        $destinationPath = $this->resolveLocation($destination);

        if (!$this->isAccessible($sourcePath) || !$this->isAccessible($destinationPath, true)) {
            throw VirtualFilesystemException::unableToMove($sourcePath, $destinationPath);
        }

        $this->inner->move($sourcePath, $destinationPath, $options);
    }

    public function get(Uuid|string $location, int $accessFlags = self::NONE): FilesystemItem|null
    {
        $path = $this->resolveLocation($location);

        if ($this->isTrailPath($path) || $this->isAllowedPath($path)) {
            return $this->getTrailItem($path);
        }

        if (!$this->isAccessible($path, true)) {
            return null;
        }

        return $this->inner->get($path, $accessFlags)?->withStorage($this);
    }

    public function listContents(Uuid|string $location, bool $deep = false, int $accessFlags = self::NONE): FilesystemItemIterator
    {
        return new FilesystemItemIterator($this->doListContents($this->resolveLocation($location), $deep, $accessFlags));
    }

    public function getLastModified(Uuid|string $location, int $accessFlags = self::NONE): int
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location), true)) {
            throw VirtualFilesystemException::unableToRetrieveMetadata($path);
        }

        return $this->inner->getLastModified($path, $accessFlags);
    }

    public function getFileSize(Uuid|string $location, int $accessFlags = self::NONE): int
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location), true)) {
            throw VirtualFilesystemException::unableToRetrieveMetadata($path);
        }

        return $this->inner->getFileSize($path, $accessFlags);
    }

    public function getMimeType(Uuid|string $location, int $accessFlags = self::NONE): string
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location), true)) {
            throw VirtualFilesystemException::unableToRetrieveMetadata($path);
        }

        return $this->inner->getMimeType($path, $accessFlags);
    }

    public function getExtraMetadata(Uuid|string $location, int $accessFlags = self::NONE): ExtraMetadata
    {
        $path = $this->resolveLocation($location);

        if (!$this->isAccessible($path, true)) {
            if (!$this->isTrailPath($path)) {
                throw VirtualFilesystemException::unableToRetrieveMetadata($path);
            }

            // Any elements from the root trail cannot provide any extra metadata as this
            // would expose information from outside the filtered scope.
            return new ExtraMetadata([]);
        }

        return $this->inner->getExtraMetadata($path, $accessFlags);
    }

    public function resolveUuid(Uuid $uuid): string
    {
        if (!$this->isAccessible($path = $this->resolveLocation($uuid))) {
            throw new UnableToResolveUuidException($uuid);
        }

        return $path;
    }

    public function generatePublicUri(Uuid|string $location, OptionsInterface|null $options = null): UriInterface|null
    {
        if (!$this->isAccessible($path = $this->resolveLocation($location))) {
            return null;
        }

        return $this->inner->generatePublicUri($path, $options);
    }

    /**
     * Returns the first non-virtual path or null if none exists.
     */
    public function getFirstNonVirtualDirectory(): string|null
    {
        foreach ($this->listContents('', true) as $item) {
            if (!$this->isTrailPath($path = $item->getPath())) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return \Generator<FilesystemItem>
     */
    private function doListContents(string $path, bool $deep, int $accessFlags): \Generator
    {
        // Virtual root trail: yield items directly
        if ($this->isTrailPath($path) && !\in_array('', $this->prefixPaths, true)) {
            foreach ($this->getAccessibleDirectSubPaths($path) as $subPath) {
                yield $this->getTrailItem($subPath);

                if ($deep) {
                    yield from $this->doListContents($subPath, true, $accessFlags);
                }
            }

            return;
        }

        if (!$this->isAccessible($path, true)) {
            return;
        }

        // Accessible directories: delegate to inner storage
        foreach ($this->inner->listContents($path, $deep, $accessFlags) as $item) {
            yield $item->withStorage($this);
        }
    }

    /**
     * @throws UnableToResolveUuidException
     */
    private function resolveLocation(Uuid|string $location): string
    {
        if ($location instanceof Uuid) {
            return $this->inner->resolveUuid($location);
        }

        return $location;
    }

    /**
     * Test if a given resource path is covered by the allowed prefix paths or part of the
     * root trail. If $requireFullOwnership is set to true, the root trail is ignored.
     */
    private function isAccessible(string $path, bool $requireFullOwnership = false): bool
    {
        foreach ($this->prefixPaths as $prefixPath) {
            if (Path::isBasePath($prefixPath, $path)) {
                return true;
            }
        }

        return !$requireFullOwnership && $this->isTrailPath($path);
    }

    private function isTrailPath(string $path): bool
    {
        return \in_array($path, $this->trailPaths, true);
    }

    private function isAllowedPath(string $path): bool
    {
        return \in_array($path, $this->prefixPaths, true);
    }

    /**
     * @return list<string>
     */
    private function getAccessibleDirectSubPaths(string $trailPath): array
    {
        $accessiblePaths = array_filter(
            [...$this->prefixPaths, ...$this->trailPaths],
            static fn (string $path): bool => Path::getDirectory($path) === $trailPath && $path !== $trailPath,
        );

        sort($accessiblePaths);

        return $accessiblePaths;
    }

    private function getTrailItem(string $path): FilesystemItem
    {
        return new FilesystemItem(
            false,
            $path,
            extraMetadata: new ExtraMetadata([]),
            storage: $this,
        );
    }
}
