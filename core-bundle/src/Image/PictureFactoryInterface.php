<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\ImageInterface;
use Contao\Image\PictureGeneratorInterface;
use Contao\Image\PictureConfigurationInterface;

/**
 * Picture factory interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureFactoryInterface
{
    /**
     * Constructor.
     *
     * @param PictureGeneratorInterface $pictureGenerator The picture generator
     * @param ImageFactoryInterface     $imageFactory     The image factory
     * @param ContaoFrameworkInterface  $framework        The Contao framework
     * @param bool                      $bypassCache      True to bypass the image cache
     * @param array                     $imagineOptions   The options for Imagine save
     */
    public function __construct(
        PictureGeneratorInterface $pictureGenerator,
        ImageFactoryInterface $imageFactory,
        ContaoFrameworkInterface $framework,
        $bypassCache,
        array $imagineOptions
    );

    /**
     * Creates a Picture object.
     *
     * @param string|ImageInterface                   $path The path to the source image or an Image object
     * @param int|array|PictureConfigurationInterface $size The ID of an image size
     *                                                      or an array with width height and resize mode
     *                                                      or a PictureConfiguration object
     *
     * @return PictureInterface The created Picture object
     */
    public function create($path, $size = null);
}
