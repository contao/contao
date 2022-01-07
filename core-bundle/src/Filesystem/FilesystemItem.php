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
     * @var int|\Closure(self):int
     */
    private $fileSize;

    /**
     * @var string|\Closure(self):string
     */
    private $mimeType;

    /**
     * @var array<string, mixed>|\Closure(self):array<string, mixed>
     */
    private $extraMetadata;

    /**
     * @param int|(\Closure(self):int|null)|null $lastModified
     * @param int|\Closure(self):int $fileSize
     * @param string|\Closure(self):string $mimeType
     * @param array<string, mixed>|\Closure(self):array<string, mixed> $extraMetadata
     */
    public function __construct(bool $isFile, string $path, $lastModified = 0, $fileSize = 0, $mimeType = '', $extraMetadata = [])
    {
        $this->isFile = $isFile;
        $this->path = $path;
        $this->lastModified = $lastModified;
        $this->fileSize = $fileSize;
        $this->mimeType = $mimeType;
        $this->extraMetadata = $extraMetadata;
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

        return new self(
            false,
            $path,
            $attributes->lastModified(),
        );
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

    public function getLastModified(): ?int
    {
        $this->resolveIfClosure($this->lastModified);

        /** @var ?int */
        return $this->lastModified;
    }

    public function getFileSize(): int
    {
        $this->assertIsFile(__FUNCTION__);
        $this->resolveIfClosure($this->fileSize);

        /** @var int */
        return $this->fileSize;
    }

    public function getMimeType(): string
    {
        $this->assertIsFile(__FUNCTION__);
        $this->resolveIfClosure($this->mimeType);

        /** @var string */
        return $this->mimeType;
    }

    public function getExtraMetadata(): array
    {
        $this->assertIsFile(__FUNCTION__);
        $this->resolveIfClosure($this->extraMetadata);

        /** @var array */
        return $this->extraMetadata;
    }

    private function assertIsFile(string $method): void
    {
        if (!$this->isFile) {
            throw new \LogicException("Cannot call $method() on a non-file filesystem item.");
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
