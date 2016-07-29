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
use Contao\Image\PictureGeneratorInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationInterface;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\ImageSizeItemModel;

/**
 * Creates Picture objects.
 *
 * @author Martin Auswöger <martin@auswoeger.com>
 */
class PictureFactory implements PictureFactoryInterface
{
    /**
     * @var PictureGeneratorInterface
     */
    private $pictureGenerator;

    /**
     * @var ImageFactoryInterface
     */
    private $imageFactory;

    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var bool
     */
    private $bypassCache;

    /**
     * @var array
     */
    private $imagineOptions;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        PictureGeneratorInterface $pictureGenerator,
        ImageFactoryInterface $imageFactory,
        ContaoFrameworkInterface $framework,
        $bypassCache,
        array $imagineOptions
    ) {
        $this->pictureGenerator = $pictureGenerator;
        $this->imageFactory = $imageFactory;
        $this->framework = $framework;
        $this->bypassCache = (bool) $bypassCache;
        $this->imagineOptions = $imagineOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function create($path, $size = null)
    {
        if (is_array($size) && isset($size[2]) && 1 === substr_count($size[2], '_')) {
            $image = $this->imageFactory->create($path, $size);
            $config = new PictureConfiguration();
        } else {
            if (is_object($path) && $path instanceof ImageInterface) {
                $image = $path;
            } else {
                $image = $this->imageFactory->create($path);
            }

            if (is_object($size) && $size instanceof PictureConfigurationInterface) {
                $config = $size;
            } else {
                $config = $this->createConfig($size);
            }
        }

        return $this->pictureGenerator->generate(
            $image,
            $config,
            (new ResizeOptions())
                ->setImagineOptions($this->imagineOptions)
                ->setBypassCache($this->bypassCache)
        );
    }

    /**
     * Creates a picture configuration.
     *
     * @param int|array $size
     *
     * @return PictureConfiguration
     */
    private function createConfig($size)
    {
        if (!is_array($size)) {
            $size = [0, 0, $size];
        }

        $config = new PictureConfiguration();

        if (!isset($size[2]) || !is_numeric($size[2])) {
            $resizeConfig = new ResizeConfiguration();

            if (isset($size[0]) && $size[0]) {
                $resizeConfig->setWidth($size[0]);
            }

            if (isset($size[1]) && $size[1]) {
                $resizeConfig->setHeight($size[1]);
            }

            if (isset($size[2]) && $size[2]) {
                $resizeConfig->setMode($size[2]);
            }

            $configItem = new PictureConfigurationItem();
            $configItem->setResizeConfig($resizeConfig);
            $config->setSize($configItem);

            return $config;
        }

        $config->setSize(
            $this->createConfigItem(
                $this->framework->getAdapter('Contao\ImageSizeModel')->findByPk($size[2])
            )
        );

        $imageSizeItems = $this->framework
            ->getAdapter('Contao\\ImageSizeItemModel')
            ->findVisibleByPid($size[2], ['order' => 'sorting ASC'])
        ;

        if (null !== $imageSizeItems) {
            $configItems = [];

            foreach ($imageSizeItems as $imageSizeItem) {
                $configItems[] = $this->createConfigItem($imageSizeItem);
            }

            $config->setSizeItems($configItems);
        }

        return $config;
    }

    /**
     * Creates a picture configuration item.
     *
     * @param ImageSizeItemModel $imageSize
     *
     * @return PictureConfigurationItem
     */
    private function createConfigItem($imageSize)
    {
        $configItem = new PictureConfigurationItem();
        $resizeConfig = new ResizeConfiguration();

        if (null !== $imageSize) {
            $resizeConfig
                ->setWidth($imageSize->width)
                ->setHeight($imageSize->height)
                ->setMode($imageSize->resizeMode)
                ->setZoomLevel($imageSize->zoom)
            ;

            $configItem
                ->setResizeConfig($resizeConfig)
                ->setSizes($imageSize->sizes)
                ->setDensities($imageSize->densities)
            ;

            if (isset($imageSize->media)) {
                $configItem->setMedia($imageSize->media);
            }
        }

        return $configItem;
    }
}
