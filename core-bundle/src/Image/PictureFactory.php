<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\CoreBundle\Adapter\AdapterFactoryInterface;

/**
 * Creates Picture objects
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureFactory
{
    /**
     * @var ImageFactory
     */
    private $imageFactory;

    /**
     * @var AdapterFactory
     */
    private $adapterFactory;

    /**
     * Constructor.
     *
     * @param ImageFactory            $imageFactory   The image factory
     * @param AdapterFactoryInterface $adapterFactory The adapter factory
     */
    public function __construct(
        ImageFactory $imageFactory,
        AdapterFactoryInterface $adapterFactory
    ) {
        $this->imageFactory = $imageFactory;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * Creates a Picture object
     *
     * @param string    $path The path to the source image
     * @param int|array $size The ID of an image size or an array with width
     *                        height and resize mode
     *
     * @return Picture The created Picture object
     */
    public function create($path, $size)
    {
        // Create an `Image` and a `PictureConfiguration`, pass it to
        // `PictureGenerator` and return the resulting `Picture`.
    }
}
