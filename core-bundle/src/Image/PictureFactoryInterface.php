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

use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureInterface;
use Contao\Image\ResizeOptions;

interface PictureFactoryInterface
{
    /**
     * Sets the default densities for generating pictures.
     */
    public function setDefaultDensities(string $densities): static;

    /**
     * Creates a Picture object.
     */
    public function create(ImageInterface|string $path, PictureConfiguration|array|int|string|null $size = null, ResizeOptions|null $options = null): PictureInterface;
}
