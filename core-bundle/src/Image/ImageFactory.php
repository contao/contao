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
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param Resizer                  $resizer    The resizer object
     * @param ImagineInterface         $imagine    The imagine object
     * @param Filesystem               $filesystem The filesystem object
     * @param ContaoFrameworkInterface $framework  The Contao framework
     */
    public function __construct(
        Resizer $resizer,
        ImagineInterface $imagine,
        Filesystem $filesystem,
        ContaoFrameworkInterface $framework
    ) {
        $this->resizer = $resizer;
        $this->imagine = $imagine;
        $this->filesystem = $filesystem;
        $this->framework = $framework;
    }

    /**
     * Creates an Image object
     *
     * @param string    $path       The path to the source image
     * @param int|array $size       The ID of an image size or an array with
     *                              width height and resize mode
     * @param string    $targetPath The absolute target path
     *
     * @return Image The created image object
     */
    public function create($path, $size = null, $targetPath = null)
    {
        $image = new Image($this->imagine, $this->filesystem, (string) $path);

        if (null === $size) {
            return $image;
        }

        $resizeConfig = $this->createResizeConfig($size);

        return $this->resizer->resize($image, $resizeConfig, $targetPath);
    }

    /**
     * Creates a ResizeConfiguration object
     *
     * @param int|array $size The ID of an image size or an array with width
     *                        height and resize mode
     *
     * @return ResizeConfiguration The resize configuration
     */
    private function createResizeConfig($size)
    {
        if (!is_array($size)) {
            $size = [0, 0, $size];
        }

        $config = new ResizeConfiguration;

        if (isset($size[2]) && is_numeric($size[2])) {

            $imageSize = $this->framework
                ->getAdapter('Contao\\ImageSizeModel')
                ->findByPk($size[2]);

            if (null !== $imageSize) {
                $config
                    ->setWidth($imageSize->width)
                    ->setHeight($imageSize->height)
                    ->setMode($imageSize->resizeMode)
                    ->setZoomLevel($imageSize->zoom);

                return $config;
            }

        }

        if (isset($size[0]) && $size[0]) {
            $config->setWidth($size[0]);
        }
        if (isset($size[1]) && $size[1]) {
            $config->setHeight($size[1]);
        }
        if (isset($size[2]) && $size[2]) {
            $config->setMode($size[2]);
        }

        return $config;
    }
}
