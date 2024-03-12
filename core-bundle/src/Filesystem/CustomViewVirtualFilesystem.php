<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

class CustomViewVirtualFilesystem implements VirtualFilesystemInterface
{
    /**
     * @var array<string|int, string>
     */
    private array $views;

    /**
     * @param VirtualFilesystemInterface $virtualFilesystem
     * @param array<string|int, string>  $views             A dictionary "label/path" => "prefix/path"
     */
    public function __construct(private readonly VirtualFilesystemInterface $virtualFilesystem, array $views)
    {
        $views = array_map(static fn (string $path): string => Path::canonicalize($path), $views);
        $this->views = $views;

        // Validate labels
        foreach (array_keys($views) as $label) {
            if ('' === $label) {
                throw new \InvalidArgumentException('A view label cannot be empty.');
            }

            if (str_contains((string) $label, '/')) {
                throw new \InvalidArgumentException(sprintf('A view label cannot contain slashes, got "%s".', $label));
            }
        }

        sort($views);

        // Validate view paths
        for ($i = 0; $i < \count($views) - 1; ++$i) {
            if (str_starts_with($views[$i + 1], "$views[$i]/")) {
                throw new \InvalidArgumentException(sprintf('Invalid custom view configuration for virtual filesystem: Path "%s" is already covered by path "%s".', $views[$i + 1], $views[$i]));
            }
        }
    }

    public function has(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            return false;
        }

        return $this->virtualFilesystem->has($realLocation, $accessFlags);
    }

    public function fileExists(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            return false;
        }

        return $this->virtualFilesystem->fileExists($realLocation, $accessFlags);
    }

    public function directoryExists(Uuid|string $location, int $accessFlags = self::NONE): bool
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            return false;
        }

        return $this->virtualFilesystem->directoryExists($realLocation, $accessFlags);
    }

    public function read(Uuid|string $location): string
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToRead((string) $location);
        }

        return $this->virtualFilesystem->read($realLocation);
    }

    public function readStream(Uuid|string $location)
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToRead((string) $location);
        }

        return $this->virtualFilesystem->readStream($realLocation);
    }

    public function write(Uuid|string $location, string $contents, array $options = []): void
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToWrite((string) $location);
        }

        $this->virtualFilesystem->write($realLocation, $contents, $options);
    }

    public function writeStream(Uuid|string $location, $contents, array $options = []): void
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToWrite((string) $location);
        }

        $this->virtualFilesystem->writeStream($realLocation, $contents, $options);
    }

    public function delete(Uuid|string $location): void
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToDelete((string) $location);
        }

        $this->virtualFilesystem->delete($realLocation);
    }

    public function deleteDirectory(Uuid|string $location): void
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToDeleteDirectory((string) $location);
        }

        $this->virtualFilesystem->deleteDirectory($realLocation);
    }

    public function createDirectory(Uuid|string $location, array $options = []): void
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToCreateDirectory((string) $location);
        }

        $this->virtualFilesystem->createDirectory($realLocation, $options);
    }

    public function copy(Uuid|string $source, string $destination, array $options = []): void
    {
        if (null === ($realSource = $this->translateLocation($source)) || null === ($realDestination = $this->translateLocation($destination))) {
            throw VirtualFilesystemException::unableToCopy((string) $source, $destination);
        }

        $this->virtualFilesystem->copy($realSource, $realDestination);
    }

    public function move(Uuid|string $source, string $destination, array $options = []): void
    {
        if (null === ($realSource = $this->translateLocation($source)) || null === ($realDestination = $this->translateLocation($destination))) {
            throw VirtualFilesystemException::unableToMove((string) $source, $destination);
        }

        $this->virtualFilesystem->move($realSource, $realDestination);
    }

    public function get(Uuid|string $location, int $accessFlags = self::NONE): FilesystemItem|null
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            return null;
        }

        return $this->virtualFilesystem->get($realLocation, $accessFlags);
    }

    public function listContents(Uuid|string $location, bool $deep = false, int $accessFlags = self::NONE): FilesystemItemIterator
    {
        if ('' === $location) {
            return new FilesystemItemIterator($this->doListViews($deep, $accessFlags));
        }

        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToListContents((string) $location);
        }

        return $this->virtualFilesystem->listContents($realLocation, $deep, $accessFlags);
    }

    public function getLastModified(Uuid|string $location, int $accessFlags = self::NONE): int
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToRetrieveMetadata((string) $location);
        }

        return $this->virtualFilesystem->getLastModified($realLocation, $accessFlags);
    }

    public function getFileSize(Uuid|string $location, int $accessFlags = self::NONE): int
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToRetrieveMetadata((string) $location);
        }

        return $this->virtualFilesystem->getFileSize($realLocation, $accessFlags);
    }

    public function getMimeType(Uuid|string $location, int $accessFlags = self::NONE): string
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToRetrieveMetadata((string) $location);
        }

        return $this->virtualFilesystem->getMimeType($realLocation, $accessFlags);
    }

    public function getExtraMetadata(Uuid|string $location, int $accessFlags = self::NONE): array
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw VirtualFilesystemException::unableToRetrieveMetadata((string) $location);
        }

        return $this->virtualFilesystem->getExtraMetadata($realLocation, $accessFlags);
    }

    public function setExtraMetadata(Uuid|string $location, array $metadata): void
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            throw new \InvalidArgumentException(sprintf('Path "%s" is not a valid view of the current filesystem.', $location));
        }

        $this->virtualFilesystem->setExtraMetadata($realLocation, $metadata);
    }

    public function generatePublicUri(Uuid|string $location, OptionsInterface $options = null): UriInterface|null
    {
        if (null === ($realLocation = $this->translateLocation($location))) {
            return null;
        }

        return $this->virtualFilesystem->generatePublicUri($realLocation, $options);
    }

    /**
     * @return \Generator<FilesystemItem>
     */
    private function doListViews(bool $deep, int $accessFlags): \Generator
    {
        foreach ($this->views as $label => $path) {
            foreach ($this->virtualFilesystem->listContents($path, $deep, $accessFlags) as $item) {
                $item = $item->withPath(Path::join($label, Path::makeRelative($item->getPath(), $path)));

                yield $item;
            }
        }
    }

    private function translateLocation(Uuid|string $location): Uuid|string|null
    {
        if ($location instanceof Uuid) {
            return $location;
        }

        if (str_starts_with($location = Path::canonicalize($location), '..')) {
            throw new \OutOfBoundsException(sprintf('Virtual filesystem path "%s" must not escape the view boundary.', $location));
        }

        foreach ($this->views as $label => $prefixPath) {
            if (1 === preg_match(sprintf('/^%s\/(.*)/', preg_quote($label)), $location, $matches)) {
                return Path::join($prefixPath, $matches[1]);
            }
        }

        return null;
    }
}
