<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfigurationInterface;
use Contao\Image\PictureGeneratorInterface;
use Contao\Image\PictureInterface;

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
     * @param PictureGeneratorInterface $pictureGenerator
     * @param ImageFactoryInterface     $imageFactory
     * @param ContaoFrameworkInterface  $framework
     * @param bool                      $bypassCache
     * @param array                     $imagineOptions
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
     * @param string|ImageInterface                        $path
     * @param int|array|PictureConfigurationInterface|null $size
     *
     * @return PictureInterface
     */
    public function create($path, $size = null);
}
