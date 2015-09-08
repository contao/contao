<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\CoreBundle\Adapter\ModelRepositoryAdapter;

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
     * @var ModelRepositoryAdapter
     */
    private $repository;

    /**
     * Constructor.
     *
     * @param ImageFactory           $imageFactory The image factory
     * @param ModelRepositoryAdapter $repository   The model repository
     */
    public function __construct(
        ImageFactory $imageFactory,
        ModelRepositoryAdapter $repository
    ) {
        $this->imageFactory = $imageFactory;
        $this->repository = $repository;
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
    private function create($path, $size)
    {
    }
}
