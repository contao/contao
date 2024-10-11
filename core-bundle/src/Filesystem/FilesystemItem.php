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

use Contao\CoreBundle\File\Metadata;
use League\Flysystem\FileAttributes;
use League\Flysystem\StorageAttributes;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

/**
 * @experimental
 */
class FilesystemItem implements \Stringable
{
    /**
     * @param int|(\Closure(self):int|null)|null              $lastModified
     * @param int|\Closure(self):int|null                     $fileSize
     * @param string|\Closure(self):string|null               $mimeType
     * @param ExtraMetadata|\Closure(self):ExtraMetadata|null $extraMetadata
     */
    public function __construct(
        private readonly bool $isFile,
        private readonly string $path,
        private \Closure|int|null $lastModified = null,
        private \Closure|int|null $fileSize = null,
        private \Closure|string|null $mimeType = null,
        private \Closure|ExtraMetadata|null $extraMetadata = null,
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
                new ExtraMetadata($attributes->extraMetadata()),
            );
        }

        return new self(false, $path, $attributes->lastModified());
    }

    /**
     * @param ExtraMetadata|\Closure(self):ExtraMetadata $extraMetadata
     */
    public function withExtraMetadata(ExtraMetadata|callable $extraMetadata): self
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
     * @param int|\Closure(self):int|null        $fileSize
     * @param string|\Closure(self):string|null  $mimeType
     */
    public function withMetadataIfNotDefined(\Closure|int|null $lastModified, \Closure|int|null $fileSize, \Closure|string|null $mimeType): self
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

    public function isVideo(): bool
    {
        if (!$this->isFile()) {
            return false;
        }

        return str_starts_with($this->getMimeType(''), 'video/');
    }

    public function isAudio(): bool
    {
        if (!$this->isFile()) {
            return false;
        }

        return str_starts_with($this->getMimeType(''), 'audio/');
    }

    public function isImage(): bool
    {
        if (!$this->isFile()) {
            return false;
        }

        return str_starts_with($this->getMimeType(''), 'image/');
    }

    public function isPdf(): bool
    {
        if (!$this->isFile()) {
            return false;
        }

        return 'application/pdf' === $this->getMimeType('');
    }

    public function isSpreadsheet(): bool
    {
        if (!$this->isFile()) {
            return false;
        }

        return \in_array(
            $this->getMimeType(''),
            [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel',
                'application/msexcel',
                'application/x-msexcel',
            ],
            true,
        );
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

    public function getMimeType(string|null $default = null): string
    {
        $this->assertIsFile(__FUNCTION__);
        $exception = null;

        try {
            $this->resolveIfClosure($this->mimeType);
        } catch (VirtualFilesystemException $e) {
            $this->mimeType = null;
            $exception = $e;
        }

        if (null === $this->mimeType && null === $default) {
            throw VirtualFilesystemException::unableToRetrieveMetadata($this->path, $exception, 'A mime type could not be detected. Set the "$default" argument to suppress this exception.');
        }

        return $this->mimeType ?? $default;
    }

    public function getExtraMetadata(): ExtraMetadata
    {
        $this->resolveIfClosure($this->extraMetadata);

        return $this->extraMetadata ?? new ExtraMetadata();
    }

    public function getUuid(): Uuid|null
    {
        return $this->getExtraMetadata()['uuid'] ?? null;
    }

    public function getSchemaOrgData(): array
    {
        $fileIdentifier = $this->getPath();

        if ($this->getUuid()) {
            $fileIdentifier = '#/schema/file/'.$this->getUuid();
        }

        $type = match (true) {
            $this->isVideo() => 'VideoObject',
            $this->isAudio() => 'AudioObject',
            $this->isImage() => 'ImageObject',
            $this->isPdf() => 'DigitalDocument',
            $this->isSpreadsheet() => 'SpreadsheetDigitalDocument',
            default => 'MediaObject',
        };

        $jsonLd = array_filter([
            '@type' => $type,
            'identifier' => $fileIdentifier,
            'contentUrl' => $this->getPath(),
            'encodingFormat' => $this->getMimeType(''),
        ]);

        if ($this->getMetaData()) {
            $jsonLd = [...$this->getMetaData()->getSchemaOrgData($type), ...$jsonLd];
        }

        ksort($jsonLd);

        return $jsonLd;
    }

    private function getMetaData(): Metadata|null
    {
        return $this->getExtraMetadata()->getLocalized()?->getDefault();
    }

    private function assertIsFile(string $method): void
    {
        if (!$this->isFile) {
            throw new \LogicException(\sprintf('Cannot call %s() on a non-file filesystem item.', $method));
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
