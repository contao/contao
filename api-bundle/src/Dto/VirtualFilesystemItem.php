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

final readonly class VirtualFilesystemItem
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $path,
        public string $name,
        public bool $isFile,
        public int|null $lastModified,
        public int|null $fileSize,
        public string|null $mimeType,
        public array $metadata,
    ) {
    }
}
