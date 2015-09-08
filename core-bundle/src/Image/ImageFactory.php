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
use Contao\CoreBundle\Adapter\ConfigAdapter;
use Contao\CoreBundle\Adapter\ModelRepositoryAdapter;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates Image objects
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageFactory
{
    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var ConfigAdapter
     */
    private $config;

    /**
     * @var ModelRepositoryAdapter
     */
    private $repository;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param ImagineInterface       $imagine    The imagine object
     * @param ConfigAdapter          $config     The Contao configuration
     * @param ModelRepositoryAdapter $repository The model repository
     * @param Filesystem             $filesystem The filesystem object
     */
    public function __construct(
        ImagineInterface $imagine,
        ConfigAdapter $config,
        ModelRepositoryAdapter $repository,
        Filesystem $filesystem
    ) {
        $this->imagine = $imagine;
        $this->config = $config;
        $this->repository = $repository;
        $this->filesystem = $filesystem;
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
    private function create($path, $size)
    {
    }
}
