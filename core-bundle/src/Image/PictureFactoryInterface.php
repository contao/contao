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
     *
     * @param string $densities
     *
     * @return static
     */
    public function setDefaultDensities(/*string */$densities)/*: static*/;

    /**
     * Creates a Picture object.
     *
     * @param string|ImageInterface                      $path
     * @param int|string|array|PictureConfiguration|null $size
     * @param ResizeOptions|null                         $options
     *
     * @return PictureInterface
     */
    public function create($path, $size = null/*, ResizeOptions $options = null*/)/*: PictureInterface*/;
}
