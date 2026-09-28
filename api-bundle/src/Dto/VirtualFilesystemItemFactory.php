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

use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\CoreBundle\Filesystem\FilesystemItem;

/**
 * Keeps API-specific normalization out of the filesystem object and response DTO.
 */
final readonly class VirtualFilesystemItemFactory
{
    public function __construct(private SchemaAwareObjectNormalizer $objectNormalizer)
    {
    }

    public function create(FilesystemItem $item): VirtualFilesystemItem
    {
        return new VirtualFilesystemItem(
            $item->getPath(),
            $item->getName(),
            $item->isFile(),
            $item->getLastModified(),
            $item->isFile() ? $item->getFileSize() : null,
            $item->isFile() ? ($item->getMimeType('') ?: null) : null,
            $this->objectNormalizer->toArray($item->getExtraMetadata()),
        );
    }
}
