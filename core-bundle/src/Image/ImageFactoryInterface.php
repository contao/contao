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
use Contao\Image\ImportantPart;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;

interface ImageFactoryInterface
{
    /**
     * Creates an Image object.
     *
     * @param string|ImageInterface              $path    The absolute path to the source image or an Image object
     * @param int|array|ResizeConfiguration|null $size    An image size ID, an array with width, height and resize mode or a ResizeConfiguration object
     * @param string|ResizeOptions|null          $options The target path as string or a ResizeOptions object
     */
    public function create(ImageInterface|string $path, ResizeConfiguration|array|int|string|null $size = null, ResizeOptions|string|null $options = null): ImageInterface;

    /**
     * Returns the equivalent important part from a legacy resize mode.
     *
     * @param string $mode One of left_top, center_top, right_top, left_center, center_center, right_center, left_bottom, center_bottom, right_bottom
     */
    public function getImportantPartFromLegacyMode(ImageInterface $image, string $mode): ImportantPart;
}
