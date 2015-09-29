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
use Contao\CoreBundle\Adapter\AdapterFactoryInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates Image objects
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageFactory
{
    /**
     * @var Resizer
     */
    private $resizer;

    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var ConfigAdapter
     */
    private $config;

    /**
     * @var AdapterFactoryInterface
     */
    private $adapterFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Resizer                 $resizer        The resizer object
     * @param ImagineInterface        $imagine        The imagine object
     * @param Filesystem              $filesystem     The filesystem object
     * @param AdapterFactoryInterface $adapterFactory The adapter factory
     */
    public function __construct(
        Resizer $resizer,
        ImagineInterface $imagine,
        Filesystem $filesystem,
        AdapterFactoryInterface $adapterFactory
    ) {
        $this->resizer = $resizer;
        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
        $this->adapterFactory = $adapterFactory;
    }

    /**
     * Creates an Image object
     *
     * @param string    $path The path to the source image
     * @param int|array $size The ID of an image size or an array with width
     *                        height and resize mode
     *
     * @return Image The created image object
     */
    public function create($path, $size)
    {
        // Create an `Image` and a `ResizeConfiguration`, pass it to `Resizer`
        // and return the resulting `Image`.
    }
}
