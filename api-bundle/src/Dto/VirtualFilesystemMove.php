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

final readonly class VirtualFilesystemMove
{
    public function __construct(
        #[ApiProperty(description: 'The source path or UUID.')]
        public string $source,
        #[ApiProperty(description: 'The destination path or UUID.')]
        public string $destination,
    ) {
    }
}
