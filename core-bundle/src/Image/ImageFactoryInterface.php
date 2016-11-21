<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\Image\ImageInterface;
use Contao\Image\ImportantPartInterface;
use Contao\Image\ResizeConfigurationInterface;

/**
 * Image factory interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ImageFactoryInterface
{
    /**
     * Creates an Image object.
     *
     * @param string|ImageInterface                       $path       The path to the source image or an Image object
     * @param int|array|ResizeConfigurationInterface|null $size       An image size ID, an array with width, height and
     *                                                                resize mode or a ResizeConfiguration object
     * @param string|null                                 $targetPath
     *
     * @return ImageInterface
     */
    public function create($path, $size = null, $targetPath = null);

    /**
     * Returns the equivalent important part from a legacy resize mode.
     *
     * @param ImageInterface $image
     * @param string         $mode  One of left_top, center_top, right_top, left_center, center_center, right_center,
     *                              left_bottom, center_bottom, right_bottom
     *
     * @return ImportantPartInterface
     */
    public function getImportantPartFromLegacyMode(ImageInterface $image, $mode);
}
