<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Image\ImageInterface;
use Contao\Image\Picture;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureConfigurationInterface;
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGeneratorInterface;
use Contao\Image\PictureInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeOptions;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;

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
     * @var ContaoFramework
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
     * @var string
     */
    private $defaultDensities = '';

    public function __construct(PictureGeneratorInterface $pictureGenerator, ImageFactoryInterface $imageFactory, ContaoFramework $framework, bool $bypassCache, array $imagineOptions)
    {
        $this->pictureGenerator = $pictureGenerator;
        $this->imageFactory = $imageFactory;
        $this->framework = $framework;
        $this->bypassCache = $bypassCache;
        $this->imagineOptions = $imagineOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultDensities($densities): self
    {
        $this->defaultDensities = (string) $densities;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function create($path, $size = null): PictureInterface
    {
        $attributes = [];

        if ($path instanceof ImageInterface) {
            $image = $path;
        } else {
            $image = $this->imageFactory->create($path);
        }

        if (\is_array($size) && isset($size[2]) && \is_string($size[2]) && 1 === substr_count($size[2], '_')) {
            $image->setImportantPart($this->imageFactory->getImportantPartFromLegacyMode($image, $size[2]));
            $size[2] = ResizeConfigurationInterface::MODE_CROP;
        }

        if ($size instanceof PictureConfigurationInterface) {
            $config = $size;
        } else {
            [$config, $attributes] = $this->createConfig($size);
        }

        $picture = $this->pictureGenerator->generate(
            $image,
            $config,
            (new ResizeOptions())->setImagineOptions($this->imagineOptions)->setBypassCache($this->bypassCache)
        );

        return $this->addImageAttributes($picture, $attributes);
    }

    /**
     * Creates a picture configuration.
     *
     * @param int|array|null $size
     *
     * @return (PictureConfiguration|array<string,string>)[]
     */
    private function createConfig($size): array
    {
        if (!\is_array($size)) {
            $size = [0, 0, $size];
        }

        $config = new PictureConfiguration();
        $attributes = [];

        if (!isset($size[2]) || !is_numeric($size[2])) {
            $resizeConfig = new ResizeConfiguration();

            if (!empty($size[0])) {
                $resizeConfig->setWidth($size[0]);
            }

            if (!empty($size[1])) {
                $resizeConfig->setHeight($size[1]);
            }

            if (!empty($size[2])) {
                $resizeConfig->setMode($size[2]);
            }

            $configItem = new PictureConfigurationItem();
            $configItem->setResizeConfig($resizeConfig);

            if ($this->defaultDensities) {
                $configItem->setDensities($this->defaultDensities);
            }

            $config->setSize($configItem);

            return [$config, $attributes];
        }

        /** @var ImageSizeModel $imageSizeModel */
        $imageSizeModel = $this->framework->getAdapter(ImageSizeModel::class);
        $imageSizes = $imageSizeModel->findByPk($size[2]);

        $config->setSize($this->createConfigItem($imageSizes));

        if ($imageSizes && $imageSizes->cssClass) {
            $attributes['class'] = $imageSizes->cssClass;
        }

        /** @var ImageSizeItemModel $imageSizeItemModel */
        $imageSizeItemModel = $this->framework->getAdapter(ImageSizeItemModel::class);
        $imageSizeItems = $imageSizeItemModel->findVisibleByPid($size[2], ['order' => 'sorting ASC']);

        if (null !== $imageSizeItems) {
            $configItems = [];

            foreach ($imageSizeItems as $imageSizeItem) {
                $configItems[] = $this->createConfigItem($imageSizeItem);
            }

            $config->setSizeItems($configItems);
        }

        return [$config, $attributes];
    }

    /**
     * Creates a picture configuration item.
     *
     * @param ImageSizeModel|ImageSizeItemModel|null $imageSize
     */
    private function createConfigItem($imageSize): PictureConfigurationItem
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

    private function addImageAttributes(PictureInterface $picture, array $attributes): PictureInterface
    {
        if (empty($attributes)) {
            return $picture;
        }

        $img = $picture->getImg();

        foreach ($attributes as $attribute => $value) {
            $img[$attribute] = $value;
        }

        return new Picture($img, $picture->getSources());
    }
}
