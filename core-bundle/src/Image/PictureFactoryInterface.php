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
use Contao\Image\PictureConfigurationInterface;
use Contao\Image\PictureInterface;

/**
 * Picture factory interface.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
interface PictureFactoryInterface
{
    /**
     * Sets the default densities for generating pictures.
     *
     * @param string $densities
     *
     * @return static
     */
    public function setDefaultDensities($densities);

    /**
     * Creates a Picture object.
     *
     * @param string|ImageInterface                        $path
     * @param int|array|PictureConfigurationInterface|null $size
     *
     * @return PictureInterface
     */
    public function create($path, $size = null);
}
