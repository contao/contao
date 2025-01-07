<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Filesystem;

use Contao\CoreBundle\Filesystem\PublicUri\OptionsInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Uid\Uuid;

/**
 * This trait simply delegates all methods of the VirtualFilesystemInterface to an
 * $inner implementation. With this, decoration of only a few methods is possible
 * with less boilerplate.
 *
 * @internal
 */
trait VirtualFilesystemDecoratorTrait
{
    private readonly VirtualFilesystemInterface $inner;

    public function has(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): bool
    {
        return $this->inner->has($location, $accessFlags);
    }

    public function fileExists(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): bool
    {
        return $this->inner->fileExists($location, $accessFlags);
    }

    public function directoryExists(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): bool
    {
        return $this->inner->directoryExists($location, $accessFlags);
    }

    public function read(Uuid|string $location): string
    {
        return $this->inner->read($location);
    }

    public function readStream(Uuid|string $location)
    {
        return $this->inner->readStream($location);
    }

    public function write(Uuid|string $location, string $contents, array $options = []): void
    {
        $this->inner->write($location, $contents, $options);
    }

    public function writeStream(Uuid|string $location, $contents, array $options = []): void
    {
        $this->inner->writeStream($location, $contents, $options);
    }

    public function delete(Uuid|string $location): void
    {
        $this->inner->delete($location);
    }

    public function deleteDirectory(Uuid|string $location): void
    {
        $this->inner->deleteDirectory($location);
    }

    public function createDirectory(Uuid|string $location, array $options = []): void
    {
        $this->inner->createDirectory($location, $options);
    }

    public function copy(Uuid|string $source, string $destination, array $options = []): void
    {
        $this->inner->copy($source, $destination, $options);
    }

    public function move(Uuid|string $source, string $destination, array $options = []): void
    {
        $this->inner->move($source, $destination, $options);
    }

    public function get(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): FilesystemItem|null
    {
        return $this->inner->get($location, $accessFlags);
    }

    public function listContents(Uuid|string $location, bool $deep = false, int $accessFlags = VirtualFilesystemInterface::NONE): FilesystemItemIterator
    {
        return $this->inner->listContents($location, $deep, $accessFlags);
    }

    public function getLastModified(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): int
    {
        return $this->inner->getLastModified($location, $accessFlags);
    }

    public function getFileSize(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): int
    {
        return $this->inner->getFileSize($location, $accessFlags);
    }

    public function getMimeType(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): string
    {
        return $this->inner->getMimeType($location, $accessFlags);
    }

    public function getExtraMetadata(Uuid|string $location, int $accessFlags = VirtualFilesystemInterface::NONE): ExtraMetadata
    {
        return $this->inner->getExtraMetadata($location, $accessFlags);
    }

    public function setExtraMetadata(Uuid|string $location, ExtraMetadata $metadata): void
    {
        $this->inner->setExtraMetadata($location, $metadata);
    }

    public function resolveUuid(Uuid $uuid): string
    {
        return $this->inner->resolveUuid($uuid);
    }

    public function generatePublicUri(Uuid|string $location, OptionsInterface|null $options = null): UriInterface|null
    {
        return $this->inner->generatePublicUri($location, $options);
    }
}
