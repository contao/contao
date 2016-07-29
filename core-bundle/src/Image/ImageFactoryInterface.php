<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Imagine\Image\ImagineInterface;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\Resize\ResizerInterface;
use Contao\Image\Image\ImageInterface;
use Contao\Image\Resize\ResizeConfigurationInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Image factory interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface ImageFactoryInterface
{
    /**
     * Constructor.
     *
     * @param ResizerInterface         $resizer         The resizer object
     * @param ImagineInterface         $imagine         The imagine object
     * @param ImagineInterface         $imagineSvg      The imagine object for SVG files
     * @param Filesystem               $filesystem      The filesystem object
     * @param ContaoFrameworkInterface $framework       The Contao framework
     * @param bool                     $bypassCache     True to bypass the image cache
     * @param array                    $imagineOptions  The options for Imagine save
     * @param array                    $validExtensions Valid filename extensions
     */
    public function __construct(
        ResizerInterface $resizer,
        ImagineInterface $imagine,
        ImagineInterface $imagineSvg,
        Filesystem $filesystem,
        ContaoFrameworkInterface $framework,
        $bypassCache,
        array $imagineOptions,
        array $validExtensions
    );

    /**
     * Creates an Image object.
     *
     * @param string|ImageInterface                  $path The path to the source image or an Image object
     * @param int|array|ResizeConfigurationInterface $size The ID of an image size
     *                                                     or an array with width, height and resize mode
     *                                                     or a ResizeConfiguration object
     * @param string    $targetPath                        The absolute target path
     *
     * @return ImageInterface The created image object
     */
    public function create($path, $size = null, $targetPath = null);
}
