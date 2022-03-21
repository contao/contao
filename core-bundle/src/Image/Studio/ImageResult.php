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
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\Image\DeferredImageInterface;
use Contao\Image\DeferredResizerInterface;
use Contao\Image\Image;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureInterface;
use Contao\Image\ResizeOptions;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Path;

class ImageResult
{
    private ContainerInterface $locator;
    private ?ResizeOptions $resizeOptions;
    private string $projectDir;

    /**
     * @var string|ImageInterface
     */
    private $filePathOrImageInterface;

    /**
     * @var int|string|array|PictureConfiguration|null
     */
    private $sizeConfiguration;

    /**
     * Cached picture.
     */
    private ?PictureInterface $picture = null;

    /**
     * Cached image dimensions.
     */
    private ?ImageDimensions $originalDimensions = null;

    /**
     * @param string|ImageInterface                      $filePathOrImage
     * @param array|PictureConfiguration|int|string|null $sizeConfiguration
     *
     * @internal Use the Contao\CoreBundle\Image\Studio\Studio factory to get an instance of this class
     */
    public function __construct(ContainerInterface $locator, string $projectDir, $filePathOrImage, $sizeConfiguration = null, ResizeOptions $resizeOptions = null)
    {
        $this->locator = $locator;
        $this->projectDir = $projectDir;
        $this->filePathOrImageInterface = $filePathOrImage;
        $this->sizeConfiguration = $sizeConfiguration;
        $this->resizeOptions = $resizeOptions;
    }

    /**
     * Creates a picture with the defined size configuration.
     */
    public function getPicture(): PictureInterface
    {
        if (null !== $this->picture) {
            return $this->picture;
        }

        // Unlike the Contao\Image\PictureFactory the PictureFactoryInterface
        // does not know about ResizeOptions. We therefore check if the third
        // argument of the "create" method allows setting them.
        $canHandleResizeOptions = static function (PictureFactoryInterface $factory): bool {
            if ($factory instanceof PictureFactory) {
                return true;
            }

            $createParameters = (new \ReflectionClass($factory))
                ->getMethod('create')
                ->getParameters()
            ;

            if (!isset($createParameters[2])) {
                return false;
            }

            $type = $createParameters[2]->getType();

            return $type instanceof \ReflectionNamedType && ResizeOptions::class === $type->getName();
        };

        $factory = $this->pictureFactory();
        $arguments = [$this->filePathOrImageInterface, $this->sizeConfiguration];

        if (null !== $this->resizeOptions && $canHandleResizeOptions($factory)) {
            $arguments[] = $this->resizeOptions;
        }

        return $this->picture = $this->pictureFactory()->create(...$arguments);
    }

    /**
     * Returns the "sources" part of the current picture.
     */
    public function getSources(): array
    {
        return $this->getPicture()->getSources($this->projectDir, $this->staticUrl());
    }

    /**
     * Returns the "img" part of the current picture.
     */
    public function getImg(): array
    {
        return $this->getPicture()->getImg($this->projectDir, $this->staticUrl());
    }

    /**
     * Returns the "src" attribute of the image. This will return a URL by
     * default. Set $asPath to true to get a relative file path instead.
     */
    public function getImageSrc(bool $asPath = false): string
    {
        if ($asPath) {
            /** @var Image $image */
            $image = $this->getPicture()->getImg()['src'];

            return Path::makeRelative($image->getPath(), $this->projectDir);
        }

        return $this->getImg()['src'] ?? '';
    }

    /**
     * Returns the image dimensions of the base resource.
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
     * Returns the file path of the base resource.
     *
     * Set $absolute to true to return an absolute path instead of a path
     * relative to the project dir.
     */
    public function getFilePath(bool $absolute = false): string
    {
        $path = $this->filePathOrImageInterface instanceof ImageInterface
            ? $this->filePathOrImageInterface->getPath()
            : $this->filePathOrImageInterface;

        return $absolute ? $path : Path::makeRelative($path, $this->projectDir);
    }

    /**
     * Synchronously processes images if they are deferred.
     *
     * This will make sure that the target files physically exist instead of
     * being generated by the Contao\CoreBundle\Controller\ImagesController
     * on first access.
     */
    public function createIfDeferred(): void
    {
        $picture = $this->getPicture();
        $candidates = [];

        foreach (array_merge([$picture->getImg()], $picture->getSources()) as $source) {
            $candidates[] = $source['src'] ?? null;

            foreach ($source['srcset'] ?? [] as $srcset) {
                $candidates[] = $srcset[0] ?? null;
            }
        }

        $deferredImages = array_filter(
            $candidates,
            static fn ($image): bool => $image instanceof DeferredImageInterface
        );

        if (empty($deferredImages)) {
            return;
        }

        $resizer = $this->locator->get('contao.image.legacy_resizer');

        if (!$resizer instanceof DeferredResizerInterface) {
            throw new \RuntimeException('The "contao.image.legacy_resizer" service does not support deferred resizing.');
        }

        foreach ($deferredImages as $deferredImage) {
            $resizer->resizeDeferredImage($deferredImage);
        }
    }

    private function imageFactory(): ImageFactoryInterface
    {
        return $this->locator->get('contao.image.factory');
    }

    private function pictureFactory(): PictureFactoryInterface
    {
        return $this->locator->get('contao.image.picture_factory');
    }

    private function staticUrl(): string
    {
        return $this->locator->get('contao.assets.files_context')->getStaticUrl();
    }
}
