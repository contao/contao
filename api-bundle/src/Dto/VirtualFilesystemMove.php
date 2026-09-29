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

final readonly class VirtualFilesystemMove
{
    public function __construct(
        public string $source,
        public string $destination,
    ) {
    }
}
