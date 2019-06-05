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
use Contao\FilesModel;
use Contao\Image\Image;
use Contao\Image\ImageInterface;
use Contao\Image\ImportantPart;
use Contao\Image\ImportantPartInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeConfigurationInterface;
use Contao\Image\ResizeOptions;
use Contao\Image\ResizerInterface;
use Contao\ImageSizeModel;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Point;
use Symfony\Component\Filesystem\Filesystem;

class ImageFactory implements ImageFactoryInterface
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
     * @var ContaoFramework
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

    public function __construct(ResizerInterface $resizer, ImagineInterface $imagine, ImagineInterface $imagineSvg, Filesystem $filesystem, ContaoFramework $framework, bool $bypassCache, array $imagineOptions, array $validExtensions)
    {
        $this->resizer = $resizer;
        $this->imagine = $imagine;
        $this->imagineSvg = $imagineSvg;
        $this->filesystem = $filesystem;
        $this->framework = $framework;
        $this->bypassCache = $bypassCache;
        $this->imagineOptions = $imagineOptions;
        $this->validExtensions = $validExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function create($path, $size = null, $targetPath = null): ImageInterface
    {
        if ($path instanceof ImageInterface) {
            $image = $path;
        } else {
            $fileExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (\in_array($fileExtension, ['svg', 'svgz'], true)) {
                $imagine = $this->imagineSvg;
            } else {
                $imagine = $this->imagine;
            }

            if (!\in_array($fileExtension, $this->validExtensions, true)) {
                throw new \InvalidArgumentException(
                    sprintf('Image type "%s" was not allowed to be processed', $fileExtension)
                );
            }

            $image = new Image((string) $path, $imagine, $this->filesystem);
        }

        if ($size instanceof ResizeConfigurationInterface) {
            /** @var ResizeConfigurationInterface $resizeConfig */
            $resizeConfig = $size;
            $importantPart = null;
        } else {
            [$resizeConfig, $importantPart] = $this->createConfig($size, $image);
        }

        if (!\is_object($path) || !($path instanceof ImageInterface)) {
            if (null === $importantPart) {
                $importantPart = $this->createImportantPart($image);
            }

            $image->setImportantPart($importantPart);
        }

        if (null === $targetPath && $resizeConfig->isEmpty()) {
            return $image;
        }

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
     * {@inheritdoc}
     */
    public function getImportantPartFromLegacyMode(ImageInterface $image, $mode): ImportantPartInterface
    {
        if (1 !== substr_count($mode, '_')) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a legacy resize mode', $mode));
        }

        $importantPart = [
            0,
            0,
            $image->getDimensions()->getSize()->getWidth(),
            $image->getDimensions()->getSize()->getHeight(),
        ];

        [$modeX, $modeY] = explode('_', $mode);

        if ('left' === $modeX) {
            $importantPart[2] = 1;
        } elseif ('right' === $modeX) {
            $importantPart[0] = $importantPart[2] - 1;
            $importantPart[2] = 1;
        }

        if ('top' === $modeY) {
            $importantPart[3] = 1;
        } elseif ('bottom' === $modeY) {
            $importantPart[1] = $importantPart[3] - 1;
            $importantPart[3] = 1;
        }

        return new ImportantPart(
            new Point($importantPart[0], $importantPart[1]),
            new Box($importantPart[2], $importantPart[3])
        );
    }

    /**
     * Creates a resize configuration object.
     *
     * @param int|array|null $size An image size or an array with width, height and resize mode
     *
     * @return (ResizeConfigurationInterface|ImportantPartInterface|null)[]
     */
    private function createConfig($size, ImageInterface $image): array
    {
        if (!\is_array($size)) {
            $size = [0, 0, $size];
        }

        $config = new ResizeConfiguration();

        if (isset($size[2]) && is_numeric($size[2])) {
            /** @var ImageSizeModel $imageModel */
            $imageModel = $this->framework->getAdapter(ImageSizeModel::class);
            $imageSize = $imageModel->findByPk($size[2]);

            if (null !== $imageSize) {
                $config
                    ->setWidth($imageSize->width)
                    ->setHeight($imageSize->height)
                    ->setMode($imageSize->resizeMode)
                    ->setZoomLevel($imageSize->zoom)
                ;
            }

            return [$config, null];
        }

        if (!empty($size[0])) {
            $config->setWidth($size[0]);
        }
        if (!empty($size[1])) {
            $config->setHeight($size[1]);
        }

        if (!isset($size[2]) || 1 !== substr_count($size[2], '_')) {
            if (!empty($size[2])) {
                $config->setMode($size[2]);
            }

            return [$config, null];
        }

        $config->setMode(ResizeConfigurationInterface::MODE_CROP);

        return [$config, $this->getImportantPartFromLegacyMode($image, $size[2])];
    }

    /**
     * Fetches the important part from the database.
     */
    private function createImportantPart(ImageInterface $image): ?ImportantPart
    {
        /** @var FilesModel $filesModel */
        $filesModel = $this->framework->getAdapter(FilesModel::class);
        $file = $filesModel->findByPath($image->getPath());

        if (null === $file || !$file->importantPartWidth || !$file->importantPartHeight) {
            return null;
        }

        $imageSize = $image->getDimensions()->getSize();

        if (
            $file->importantPartX + $file->importantPartWidth > $imageSize->getWidth()
            || $file->importantPartY + $file->importantPartHeight > $imageSize->getHeight()
        ) {
            return null;
        }

        return new ImportantPart(
            new Point((int) $file->importantPartX, (int) $file->importantPartY),
            new Box((int) $file->importantPartWidth, (int) $file->importantPartHeight)
        );
    }
}
