<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image;

use Contao\Image\PictureConfiguration;
use Contao\Image\ResizeOptions;

class PictureGenerationConfig
{
    public function __construct(
        private readonly PictureConfiguration $pictureConfiguration,
        private readonly ResizeOptions $resizeOptions,
    ) {
    }

    public function getPictureConfiguration(): PictureConfiguration
    {
        return $this->pictureConfiguration;
    }

    public function getResizeOptions(): ResizeOptions
    {
        return $this->resizeOptions;
    }
}
