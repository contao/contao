<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Image\Preview;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Contao\StringUtil;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class PreviewFactory
{
    private const MINIMUM_SIZE = 256;

    /**
     * @var iterable<int,PreviewProviderInterface>
     */
    private iterable $previewProviders;

    private ImageFactoryInterface $imageFactory;
    private PictureFactoryInterface $pictureFactory;
    private Studio $imageStudio;
    private ContaoFramework $framework;
    private string $cacheDir;
    private array $validImageExtensions;
    private string $defaultDensities = '';
    private array $predefinedSizes = [];

    /**
     * @param iterable<int,PreviewProviderInterface> $previewProviders
     */
    public function __construct(iterable $previewProviders, ImageFactoryInterface $imageFactory, PictureFactoryInterface $pictureFactory, Studio $imageStudio, ContaoFramework $framework, string $cacheDir, array $validImageExtensions)
    {
        $this->previewProviders = $previewProviders;
        $this->imageFactory = $imageFactory;
        $this->pictureFactory = $pictureFactory;
        $this->imageStudio = $imageStudio;
        $this->framework = $framework;
        $this->cacheDir = $cacheDir;
        $this->validImageExtensions = $validImageExtensions;
    }

    public function setDefaultDensities(string $densities): self
    {
        $this->defaultDensities = $densities;

        return $this;
    }

    public function setPredefinedSizes(array $predefinedSizes): void
    {
        $this->predefinedSizes = $predefinedSizes;
    }

    /**
     * @throws UnableToGeneratePreviewException|MissingPreviewProviderException
     */
    public function createPreviewFile(string $path, int $size = 0, array $previewOptions = []): string
    {
        // Supported image formats do not need an extra preview image
        if (\in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $this->validImageExtensions, true)) {
            return $path;
        }

        // Round up to the next highest power of two
        $size = (int) (2 ** log($size, 2));

        // Minimum size for previews
        $size = max(self::MINIMUM_SIZE, $size);

        $cachePath = $this->createCachePath($path, $size, $previewOptions);
        $globPattern = preg_replace('/[*?[{\\\\]/', '\\\\$0', $this->cacheDir.'/'.$cachePath).'.*';

        foreach (glob($globPattern) as $cacheFile) {
            if (\in_array(pathinfo($cacheFile, PATHINFO_EXTENSION), $this->validImageExtensions, true)) {
                return $cacheFile;
            }
        }

        $headerSize = 0;

        foreach ($this->previewProviders as $provider) {
            $headerSize = max($headerSize, $provider->getFileHeaderSize());
        }

        $header = $headerSize > 0 ? file_get_contents($path, false, null, 0, $headerSize) : '';
        $lastProviderException = null;

        foreach ($this->previewProviders as $provider) {
            if ($provider->supports($path, $header)) {
                try {
                    $format = $provider->getImageFormat($path, $size, $header, $previewOptions);
                    $targetPath = Path::join($this->cacheDir, "$cachePath.$format");

                    if (!is_dir(\dirname($targetPath))) {
                        (new Filesystem())->mkdir(\dirname($targetPath));
                    }

                    $provider->generatePreview($path, $size, $targetPath, $previewOptions);

                    return $targetPath;
                } catch (UnableToGeneratePreviewException $exception) {
                    $lastProviderException = $exception;
                }
            }
        }

        throw $lastProviderException ?? new MissingPreviewProviderException();
    }

    /**
     * @param int|string|array|ResizeConfiguration|null $size
     */
    public function createPreviewImage(string $path, $size = null, ResizeOptions $resizeOptions = null, array $previewOptions = []): ImageInterface
    {
        return $this->imageFactory
            ->create(
                $this->createPreviewFile($path, $this->getPreviewSizeFromImageSize($size), $previewOptions),
                $size,
                $resizeOptions,
            )
        ;
    }

    /**
     * @param int|string|array|PictureConfiguration|null $size
     */
    public function createPreviewPicture(string $path, $size = null, ResizeOptions $resizeOptions = null, array $previewOptions = []): PictureInterface
    {
        // Unlike the Contao\Image\PictureFactory the PictureFactoryInterface
        // does not know about ResizeOptions. We therefore check if the third
        // argument of the 'create' method allows setting them.
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

        $arguments = [$this->createPreviewFile($path, $this->getPreviewSizeFromImageSize($size), $previewOptions), $size];

        if (null !== $resizeOptions && $canHandleResizeOptions($this->pictureFactory)) {
            $arguments[] = $resizeOptions;
        }

        return $this->pictureFactory->create(...$arguments);
    }

    /**
     * @param int|string|array|PictureConfiguration|null $size
     */
    public function createPreviewFigure(string $path, $size = null, ResizeOptions $resizeOptions = null, array $previewOptions = []): Figure
    {
        return $this->createPreviewFigureBuilder($path, $size, $resizeOptions, $previewOptions)->build();
    }

    /**
     * @param int|string|array|PictureConfiguration|null $size
     */
    public function createPreviewFigureBuilder(string $path, $size = null, ResizeOptions $resizeOptions = null, array $previewOptions = []): FigureBuilder
    {
        return $this->imageStudio
            ->createFigureBuilder()
            ->fromPath($this->createPreviewFile($path, $this->getPreviewSizeFromImageSize($size), $previewOptions))
            ->setSize($size)
            ->setResizeOptions($resizeOptions)
        ;
    }

    /**
     * @param int|string|array|ResizeConfiguration|PictureConfiguration|null $size
     */
    private function getPreviewSizeFromImageSize($size): int
    {
        if ($size instanceof ResizeConfiguration) {
            return max($size->getWidth(), $size->getHeight());
        }

        if ($size instanceof PictureConfiguration) {
            $previewSize = $this->getPreviewSizeFromWidthHeightDensities(
                $size->getSize()->getResizeConfig()->getWidth(),
                $size->getSize()->getResizeConfig()->getHeight(),
                $size->getSize()->getDensities(),
            );

            foreach ($size->getSizeItems() as $sizeItem) {
                $previewSize = max(
                    $previewSize,
                    $this->getPreviewSizeFromWidthHeightDensities(
                        $size->getSize()->getResizeConfig()->getWidth(),
                        $size->getSize()->getResizeConfig()->getHeight(),
                        $size->getSize()->getDensities(),
                    ),
                );
            }

            return $previewSize;
        }

        // Support arrays in a serialized form
        $size = StringUtil::deserialize($size);

        if (!\is_array($size)) {
            $size = [0, 0, $size];
        }

        if ($predefinedSize = $this->predefinedSizes[$size[2] ?? null] ?? null) {
            $previewSize = $this->getPreviewSizeFromWidthHeightDensities(
                $predefinedSize['width'] ?? 0,
                $predefinedSize['height'] ?? 0,
                $predefinedSize['densities'],
            );

            foreach ($predefinedSize['items'] ?? [] as $sizeItem) {
                $previewSize = max(
                    $previewSize,
                    $this->getPreviewSizeFromWidthHeightDensities(
                        $sizeItem['width'] ?? 0,
                        $sizeItem['height'] ?? 0,
                        $sizeItem['densities'],
                    ),
                );
            }

            return $previewSize;
        }

        if (is_numeric($size[2])) {
            $imageSize = $this->framework->getAdapter(ImageSizeModel::class)->findByPk($size[2]);

            if (null === $imageSize) {
                return 0;
            }

            $previewSize = $this->getPreviewSizeFromWidthHeightDensities(
                (int) $imageSize->width,
                (int) $imageSize->height,
                $imageSize->densities,
            );

            $imageSizeItems = $this->framework->getAdapter(ImageSizeItemModel::class)->findVisibleByPid($size[2], ['order' => 'sorting ASC']);

            foreach ($imageSizeItems ?? [] as $sizeItem) {
                $previewSize = max(
                    $previewSize,
                    $this->getPreviewSizeFromWidthHeightDensities(
                        (int) $sizeItem->width,
                        (int) $sizeItem->height,
                        $sizeItem->densities,
                    ),
                );
            }

            return $previewSize;
        }

        return $this->getPreviewSizeFromWidthHeightDensities(
            (int) ($size[0] ?? 0),
            (int) ($size[1] ?? 0),
            $this->defaultDensities,
        );
    }

    private function getPreviewSizeFromWidthHeightDensities(int $width, int $height, string $densities): int
    {
        $dimensions = [$width, $height];
        $scaleFactors = [1];

        foreach (explode(',', $densities) as $density) {
            if ('w' === substr(trim($density), -1)) {
                $dimensions[] = (int) $density;
            } else {
                $scaleFactors[] = (float) $density;
            }
        }

        return (int) round(max(...$dimensions) * max(...$scaleFactors));
    }

    private function createCachePath(string $path, int $size, array $previewOptions): string
    {
        ksort($previewOptions);

        $hashData = [
            Path::makeRelative($path, $this->cacheDir),
            (string) $size,
            (string) filemtime($path),
            ...array_keys($previewOptions),
            ...array_values($previewOptions),
        ];

        $hash = substr(md5(implode('|', $hashData)), 0, 9);
        $pathinfo = pathinfo($path);

        return Path::join($hash[0], $pathinfo['filename'].'-'.substr($hash, 1));
    }
}
