<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Studio;

use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureInterface;
use Psr\Container\ContainerInterface;
use Webmozart\PathUtil\Path;

class ImageResult
{
    /**
     * @readonly
     *
     * @var ContainerInterface
     */
    protected $locator;

    /**
     * @readonly
     *
     * @var string|ImageInterface
     */
    protected $filePathOrImageInterface;

    /**
     * @readonly
     *
     * @var int|string|array|PictureConfiguration|null
     */
    protected $sizeConfiguration;

    /**
     * Cached picture.
     *
     * @var PictureInterface|null
     */
    protected $picture;

    /**
     * Cached image dimensions.
     *
     * @var ImageDimensions|null
     */
    protected $originalDimensions;

    /**
     * @param string|ImageInterface                      $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     *
     * @internal use the Contao\Image\Studio\Studio factory to get an instance of this class
     */
    public function __construct(ContainerInterface $locator, $filePathOrImage, $sizeConfiguration = null)
    {
        $this->locator = $locator;
        $this->filePathOrImageInterface = $filePathOrImage;
        $this->sizeConfiguration = $sizeConfiguration;
    }

    /**
     * Create a picture with the defined size configuration.
     */
    public function getPicture(): PictureInterface
    {
        if (null === $this->picture) {
            $this->picture = $this->pictureFactory()->create($this->filePathOrImageInterface, $this->sizeConfiguration);
        }

        return $this->picture;
    }

    /**
     * Return the "sources" part of the current picture.
     */
    public function getSources(): array
    {
        return $this->getPicture()->getSources($this->projectDir(), $this->staticUrl());
    }

    /**
     * Return the "img" part of the current picture.
     */
    public function getImg(): array
    {
        return $this->getPicture()->getImg($this->projectDir(), $this->staticUrl());
    }

    /**
     * Return the image "src" attribute.
     */
    public function getImageSrc(): string
    {
        return $this->getImg()['src'] ?? '';
    }

    /**
     * Return the original image dimensions.
     */
    public function getOriginalDimensions(): ImageDimensions
    {
        if (null !== $this->originalDimensions) {
            return $this->originalDimensions;
        }

        if ($this->filePathOrImageInterface instanceof ImageInterface) {
            return $this->originalDimensions = $this->filePathOrImageInterface->getDimensions();
        }

        return $this->originalDimensions = $this
            ->imageFactory()
            ->create($this->filePathOrImageInterface)
            ->getDimensions()
        ;
    }

    /**
     * Return the file path of the base resource. Set $absolute to true to
     * return an absolute path instead of a path relative to the project dir.
     */
    public function getFilePath($absolute = false): string
    {
        $path = $this->filePathOrImageInterface instanceof ImageInterface
            ? $this->filePathOrImageInterface->getPath()
            : $this->filePathOrImageInterface;

        return $absolute ? $path : Path::makeRelative($path, $this->projectDir());
    }

    protected function imageFactory(): ImageFactoryInterface
    {
        return $this->locator->get('contao.image.image_factory');
    }

    protected function pictureFactory(): PictureFactoryInterface
    {
        return $this->locator->get('contao.image.picture_factory');
    }

    protected function projectDir(): string
    {
        return $this->locator
            ->get('parameter_bag')
            ->get('kernel.project_dir')
        ;
    }

    protected function staticUrl(): string
    {
        return $this->locator
            ->get('contao.assets.files_context')
            ->getStaticUrl()
        ;
    }
}
