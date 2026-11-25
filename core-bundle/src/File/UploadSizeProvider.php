<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\File;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadSizeProvider
{
    public function __construct(
        private readonly int $maximumUploadSize,
        private readonly float|int|null $maximumPhpUploadSize = null,
    ) {
    }

    public function getMaximumUploadSize(): int
    {
        $phpMaximum = $this->maximumPhpUploadSize ?? UploadedFile::getMaxFilesize();

        return min($phpMaximum, $this->maximumUploadSize);
    }

    public function getMaximumUploadSizeInMegabytes(): float
    {
        return round($this->getMaximumUploadSize() / 1024 / 1024);
    }
}
