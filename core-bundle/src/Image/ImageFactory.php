<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Image;

use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImagineInterface;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Image\Resizer as ResizerInterface;
use Contao\Image\Image;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Image\ImportantPart;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Creates Image objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class ImageFactory
{
    /**
     * @var ResizerInterface
     */
    private $resizer;

    /**
     * @var ImagineInterface
     */
    private $imagine;

    /**
     * @var ImagineInterface
     */
    private $imagineSvg;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var bool
     */
    private $bypassCache;

    /**
     * @var array
     */
    private $imagineOptions;

    /**
     * @var array
     */
    private $validExtensions;

    /**
     * Constructor.
     *
     * @param ResizerInterface         $resizer        The resizer object
     * @param ImagineInterface         $imagine        The imagine object
     * @param ImagineInterface         $imagineSvg     The imagine object for SVG files
     * @param Filesystem               $filesystem     The filesystem object
     * @param ContaoFrameworkInterface $framework      The Contao framework
     * @param bool                     $bypassCache    True to bypass the image cache
     * @param array                    $imagineOptions The options for Imagine save
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
    ) {
        $this->resizer = $resizer;
        $this->imagine = $imagine;
        $this->imagineSvg = $imagineSvg;
        $this->filesystem = $filesystem;
        $this->framework = $framework;
        $this->bypassCache = (bool) $bypassCache;
        $this->imagineOptions = $imagineOptions;
        $this->validExtensions = $validExtensions;
    }

    /**
     * Creates an Image object.
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
        $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($fileExtension, ['svg', 'svgz'])) {
            $imagine = $this->imagineSvg;
        } else {
            $imagine = $this->imagine;
        }

        if (!in_array($fileExtension, $this->validExtensions)) {
            throw new \InvalidArgumentException('Image type "' . $fileExtension . '" was not allowed to be processed');
        }

        $image = new Image($imagine, $this->filesystem, (string) $path);
        list($resizeConfig, $importantPart) = $this->createConfig($size, $image);

        if ($resizeConfig->isEmpty() && $targetPath === null) {
            return $image;
        }

        if (null === $importantPart) {
            $importantPart = $this->createImportantPart($image->getPath());
        }
        $image->setImportantPart($importantPart);

        return $this->resizer->resize(
            $image,
            $resizeConfig,
            (new ResizeOptions())
                ->setImagineOptions($this->imagineOptions)
                ->setTargetPath($targetPath)
                ->setBypassCache($this->bypassCache)
        );
    }

    /**
     * Creates a ResizeConfiguration object.
     *
     * @param int|array $size  The ID of an image size or an array with width
     *                         height and resize mode
     * @param Image     $image The image instance
     *
     * @return array The resize configuration and important part
     */
    private function createConfig($size, Image $image)
    {
        if (!is_array($size)) {
            $size = [0, 0, $size];
        }

        $config = new ResizeConfiguration();

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
            }

            return [$config, null];
        }

        if (isset($size[0]) && $size[0]) {
            $config->setWidth($size[0]);
        }
        if (isset($size[1]) && $size[1]) {
            $config->setHeight($size[1]);
        }

        if (!isset($size[2]) || substr_count($size[2], '_') !== 1) {
            if (isset($size[2]) && $size[2]) {
                $config->setMode($size[2]);
            }

            return [$config, null];
        }

        $importantPart = [
            0,
            0,
            $image->getDimensions()->getSize()->getWidth(),
            $image->getDimensions()->getSize()->getHeight(),
        ];

        list($modeX, $modeY) = explode('_', $size[2]);

        if ($modeX === 'left') {
            $importantPart[2] = 1;
        } elseif ($modeX === 'right') {
            $importantPart[0] = $importantPart[2] - 1;
            $importantPart[2] = 1;
        }

        if ($modeY === 'top') {
            $importantPart[3] = 1;
        } elseif ($modeY === 'bottom') {
            $importantPart[1] = $importantPart[3] - 1;
            $importantPart[3] = 1;
        }

        $config->setMode(ResizeConfiguration::MODE_CROP);

        return [$config, new ImportantPart(
            new Point($importantPart[0], $importantPart[1]),
            new Box($importantPart[2], $importantPart[3])
        )];
    }

    /**
     * Fetches the important part from the database.
     *
     * @param string $path
     *
     * @return ImportantPart|null
     */
    private function createImportantPart($path)
    {
        $file = $this->framework
            ->getAdapter('Contao\\FilesModel')
            ->findByPath($path);

        if (
            null !== $file &&
            $file->importantPartWidth &&
            $file->importantPartHeight
        ) {
            return new ImportantPart(
                new Point((int) $file->importantPartX, (int) $file->importantPartY),
                new Box((int) $file->importantPartWidth, (int) $file->importantPartHeight)
            );
        }

        return;
    }
}
