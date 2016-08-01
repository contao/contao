<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Imagine\Image\ImagineInterface;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\ResizerInterface;
use Contao\Image\ImageInterface;
use Contao\Image\ResizeConfigurationInterface;
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
     * @param ResizerInterface         $resizer
     * @param ImagineInterface         $imagine
     * @param ImagineInterface         $imagineSvg
     * @param Filesystem               $filesystem
     * @param ContaoFrameworkInterface $framework
     * @param bool                     $bypassCache
     * @param array                    $imagineOptions
     * @param array                    $validExtensions
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
     * @param string|ImageInterface                  $path       The path to the source image or an Image object
     * @param int|array|ResizeConfigurationInterface $size       The ID of an image size, an array with width, height
     *                                                           and resize mode or a ResizeConfiguration object
     * @param string                                 $targetPath
     *
     * @return ImageInterface
     */
    public function create($path, $size = null, $targetPath = null);
}
