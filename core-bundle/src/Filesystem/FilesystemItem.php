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

use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Filesystem\Path;

/**
 * @experimental
 */
class FilesystemItem implements \Stringable
{
    /**
     * @param int|(\Closure(self):int|null)|null                       $lastModified
     * @param int|\Closure(self):int|null                              $fileSize
     * @param string|\Closure(self):string|null                        $mimeType
     * @param array<string, mixed>|\Closure(self):array<string, mixed> $extraMetadata
     */
    public function __construct(
        private bool $isFile,
        private string $path,
        private \Closure|int|null $lastModified = null,
        private \Closure|int|null $fileSize = null,
        private \Closure|string|null $mimeType = null,
        private \Closure|array $extraMetadata = [],
    ) {
    }

    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @internal
     */
    public static function fromStorageAttributes(StorageAttributes $attributes, string $pathPrefix = ''): self
    {
        $path = Path::join($pathPrefix, $attributes->path());

        if ($attributes instanceof FileAttributes) {
            return new self(
                true,
                $path,
                $attributes->lastModified(),
                $attributes->fileSize(),
                $attributes->mimeType(),
                $attributes->extraMetadata()
            );
        }

        return new self(false, $path, $attributes->lastModified());
    }

    /**
     * @param array<string, mixed>|\Closure(self):array<string, mixed> $extraMetadata
     */
    public function withExtraMetadata(array|callable $extraMetadata): self
    {
        return new self(
            $this->isFile,
            $this->path,
            $this->lastModified,
            $this->fileSize,
            $this->mimeType,
            $extraMetadata,
        );
    }

    /**
     * @param int|(\Closure(self):int|null)|null $lastModified
     * @param int|\Closure(self):int|null $fileSize
     * @param string|\Closure(self):string|null $mimeType
     */
    public function withMetadataIfNotDefined($lastModified, $fileSize, $mimeType): self
    {
        return new self(
            $this->isFile,
            $this->path,
            $this->lastModified ?? $lastModified,
            $this->fileSize ?? $fileSize,
            $this->mimeType ?? $mimeType,
            $this->extraMetadata,
        );
    }

    public function withPath(string $path): self
    {
        return new self(
            $this->isFile,
            $path,
            $this->lastModified,
            $this->fileSize,
            $this->mimeType,
            $this->extraMetadata,
        );
    }

    public function isFile(): bool
    {
        return $this->isFile;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getExtension(bool $forceLowerCase = false): string
    {
        return Path::getExtension($this->path, $forceLowerCase);
    }

    public function getName(): string
    {
        return basename($this->path);
    }

    public function getLastModified(): int|null
    {
        $this->resolveIfClosure($this->lastModified);

        return $this->lastModified;
    }

    public function getFileSize(): int
    {
        $this->assertIsFile(__FUNCTION__);
        $this->resolveIfClosure($this->fileSize);

        return $this->fileSize ?? 0;
    }

    public function getMimeType(): string
    {
        $this->assertIsFile(__FUNCTION__);
        $this->resolveIfClosure($this->mimeType);

        return $this->mimeType ?? '';
    }

    public function getExtraMetadata(): array
    {
        $this->assertIsFile(__FUNCTION__);
        $this->resolveIfClosure($this->extraMetadata);

        return $this->extraMetadata;
    }

    private function assertIsFile(string $method): void
    {
        if (!$this->isFile) {
            throw new \LogicException(sprintf('Cannot call %s() on a non-file filesystem item.', $method));
        }
    }

    /**
     * Evaluates closures to retrieve the value.
     */
    private function resolveIfClosure(mixed &$property): void
    {
        if ($property instanceof \Closure) {
            $property = $property($this);
        }
    }
}
