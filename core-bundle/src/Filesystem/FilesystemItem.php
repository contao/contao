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
class FilesystemItem
{
    private bool $isFile;
    private string $path;

    /**
     * @var int|(\Closure(self):int|null)|null
     */
    private $lastModified;

    /**
     * @var int|\Closure(self):int|null
     */
    private $fileSize;

    /**
     * @var string|\Closure(self):string|null
     */
    private $mimeType;

    /**
     * @var array<string, mixed>|\Closure(self):array<string, mixed>
     */
    private $extraMetadata;

    /**
     * @var string|(\Closure(self):string|null)|null
     */
    private $publicUri;

    /**
     * @param int|(\Closure(self):int|null)|null $lastModified
     * @param int|\Closure(self):int|null $fileSize
     * @param string|\Closure(self):string|null $mimeType
     * @param array<string, mixed>|\Closure(self):array<string, mixed> $extraMetadata
     * @param string|(\Closure(self):string|null)|null $publicUri
     */
    public function __construct(bool $isFile, string $path, $lastModified = null, $fileSize = null, $mimeType = null, $extraMetadata = [], $publicUri = null)
    {
        $this->isFile = $isFile;
        $this->path = $path;
        $this->lastModified = $lastModified;
        $this->fileSize = $fileSize;
        $this->mimeType = $mimeType;
        $this->extraMetadata = $extraMetadata;

        // todo: We should probably implement this as a callback instead of a
        //       parameter, because it will never be known in advance; would
        //       also allow to pass the options. If options are serializable,
        //       results could still be cached here.
        $this->publicUri = $publicUri;
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
    public function withExtraMetadata($extraMetadata): self
    {
        return new self(
            $this->isFile,
            $this->path,
            $this->lastModified,
            $this->fileSize,
            $this->mimeType,
            $extraMetadata,
            $this->publicUri,
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
            $this->publicUri,
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
            $this->publicUri,
        );
    }

    /**
     * @param string|(\Closure(self):string|null)|null $publicUri
     */
    public function withPublicUri($publicUri): self
    {
        return new self(
            $this->isFile,
            $this->path,
            $this->lastModified,
            $this->fileSize,
            $this->mimeType,
            $this->extraMetadata,
            $publicUri,
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

    public function getLastModified(): ?int
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

    public function getPublicUri(): ?string
    {
        $this->assertIsFile(__FUNCTION__);
        $this->resolveIfClosure($this->publicUri);

        return $this->publicUri;
    }

    private function assertIsFile(string $method): void
    {
        if (!$this->isFile) {
            throw new \LogicException(sprintf('Cannot call %s() on a non-file filesystem item.', $method));
        }
    }

    /**
     * Evaluates closures to retrieve the value.
     *
     * @param mixed $property
     */
    private function resolveIfClosure(&$property): void
    {
        if ($property instanceof \Closure) {
            $property = $property($this);
        }
    }
}
