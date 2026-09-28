<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;

final readonly class VirtualFilesystemItem
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $path,
        public string $name,
        public bool $isFile,
        public int|null $lastModified,
        public int|null $fileSize,
        public string|null $mimeType,
        public string|null $uuid,
        public array $metadata,
    ) {
    }

    public static function fromFilesystemItem(FilesystemItem $item): self
    {
        $extraMetadata = $item->getExtraMetadata();
        $metadata = $extraMetadata->all();
        $uuid = $item->getUuid();

        unset($metadata['uuid']);

        if ($localizedMetadata = $extraMetadata->getLocalized()) {
            $metadata['localized'] = array_map(static fn (Metadata $item): array => $item->all(), $localizedMetadata->all());
        }

        return new self(
            $item->getPath(),
            $item->getName(),
            $item->isFile(),
            $item->getLastModified(),
            $item->isFile() ? $item->getFileSize() : null,
            $item->isFile() ? ($item->getMimeType('') ?: null) : null,
            $uuid?->toRfc4122(),
            $metadata,
        );
    }
}
