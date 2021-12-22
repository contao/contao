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
use Contao\Image\DeferredImageStorageInterface;
use Contao\Image\ImageDimensions;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Contao\StringUtil;
use Imagine\Image\Box;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class PreviewFactory
{
    private const MINIMUM_SIZE = 256;

    /**
     * @var iterable<int,PreviewProviderInterface>
     */
    private iterable $previewProviders;

    private DeferredImageStorageInterface $deferredStorage;
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
    public function __construct(iterable $previewProviders, DeferredImageStorageInterface $deferredStorage, ImageFactoryInterface $imageFactory, PictureFactoryInterface $pictureFactory, Studio $imageStudio, ContaoFramework $framework, string $cacheDir, array $validImageExtensions)
    {
        $this->previewProviders = $previewProviders;
        $this->deferredStorage = $deferredStorage;
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
    public function createPreview(string $path, int $size = 0, array $previewOptions = []): ImageInterface
    {
        // Supported image formats do not need an extra preview image
        if (\in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $this->validImageExtensions, true)) {
            return $this->imageFactory->create($path);
        }

        $size = $this->normalizeSize($size);

        if (null !== ($cachedPreview = $this->getCachedPreview($path, $size, $previewOptions))) {
            return $this->imageFactory->create($cachedPreview);
        }

        $cachePath = $this->createCachePath($path, $size, $previewOptions);
        $header = $this->getHeader($path);
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

                    return $this->imageFactory->create($targetPath);
                } catch (UnableToGeneratePreviewException $exception) {
                    $lastProviderException = $exception;
                }
            }
        }

        throw $lastProviderException ?? new MissingPreviewProviderException();
    }

    /**
     * @throws UnableToGeneratePreviewException|MissingPreviewProviderException
     */
    public function createDeferredPreview(string $path, int $size = 0, array $previewOptions = []): ImageInterface
    {
        $size = $this->normalizeSize($size);

        if (null !== ($cachedPreview = $this->getCachedPreview($path, $size, $previewOptions))) {
            return $this->imageFactory->create($cachedPreview);
        }

        $cachePath = $this->createCachePath($path, $size, $previewOptions);

        if ($this->deferredStorage->has($cachePath)) {
            $data = $this->deferredStorage->get($cachePath);

            return new DeferredPreview(
                Path::join($this->cacheDir, "$cachePath.{$data['format']}"),
                new ImageDimensions(
                    new Box($data['dimensions']['width'], $data['dimensions']['height']),
                    $data['dimensions']['relative'],
                    $data['dimensions']['undefined'],
                    $data['dimensions']['orientation'],
                ),
            );
        }

        $header = $this->getHeader($path);
        $lastProviderException = null;

        foreach ($this->previewProviders as $provider) {
            if ($provider->supports($path, $header)) {
                try {
                    $format = $provider->getImageFormat($path, $size, $header, $previewOptions);
                    $dimensions = $provider->getDimensions($path, $size, $header, $previewOptions);

                    $this->deferredStorage->set($cachePath, [
                        'path' => Path::makeRelative($path, $this->cacheDir),
                        'format' => $format,
                        'size' => $size,
                        'options' => $previewOptions,
                        'dimensions' => [
                            'width' => $dimensions->getSize()->getWidth(),
                            'height' => $dimensions->getSize()->getHeight(),
                            'relative' => $dimensions->isRelative(),
                            'undefined' => $dimensions->isUndefined(),
                            'orientation' => $dimensions->getOrientation(),
                        ],
                        'provider' => \get_class($provider),
                    ]);

                    return new DeferredPreview(
                        Path::join($this->cacheDir, "$cachePath.$format"),
                        $dimensions,
                    );
                } catch (UnableToGeneratePreviewException $exception) {
                    $lastProviderException = $exception;
                }
            }
        }

        throw $lastProviderException ?? new MissingPreviewProviderException();
    }

    /**
     * @throws UnableToGeneratePreviewException|MissingPreviewProviderException
     */
    public function createPreviewFromDeferred(DeferredPreview $image): ImageInterface
    {
        $cachePath = preg_replace(
            '(\.(?:'.implode('|', array_map('preg_quote', $this->validImageExtensions)).')$)',
            '',
            Path::makeRelative($image->getPath(), $this->cacheDir),
        );

        $data = $this->deferredStorage->getLocked($cachePath);
        $path = Path::makeAbsolute($data['path'], $this->cacheDir);
        $targetPath = Path::join($this->cacheDir, "$cachePath.{$data['format']}");
        $size = $data['size'];
        $previewOptions = $data['options'];

        foreach ($this->previewProviders as $provider) {
            if (\get_class($provider) !== $data['provider']) {
                continue;
            }

            try {
                $provider->generatePreview($path, $size, $targetPath, $previewOptions);
            } catch (\Throwable $exception) {
                $this->deferredStorage->releaseLock($targetPath);

                throw $exception;
            }

            $this->deferredStorage->delete($cachePath);

            return $this->imageFactory->create($targetPath);
        }

        $this->deferredStorage->releaseLock($targetPath);

        throw new MissingPreviewProviderException();
    }

    /**
     * @param int|string|array|ResizeConfiguration|null $size
     */
    public function createPreviewImage(string $path, $size = null, ResizeOptions $resizeOptions = null, array $previewOptions = []): ImageInterface
    {
        return $this->imageFactory
            ->create(
                $this->createPreview($path, $this->getPreviewSizeFromImageSize($size), $previewOptions),
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

        $arguments = [$this->createPreview($path, $this->getPreviewSizeFromImageSize($size), $previewOptions), $size];

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
            ->fromImage($this->createPreview($path, $this->getPreviewSizeFromImageSize($size), $previewOptions))
            ->setSize($size)
            ->setResizeOptions($resizeOptions)
        ;
    }

    private function normalizeSize(int $size): int
    {
        // Minimum size for previews
        $size = 0 === $size ? 0 : max(self::MINIMUM_SIZE, $size);

        // Round up to the next highest power of two
        return (int) (2 ** ceil(log($size, 2)));
    }

    private function getCachedPreview(string $path, int $size, array $previewOptions): ?string
    {
        $cachePath = $this->createCachePath($path, $size, $previewOptions);
        $globPattern = preg_replace('/[*?[{\\\\]/', '\\\\$0', $this->cacheDir.'/'.$cachePath).'.*';

        foreach (glob($globPattern) as $cacheFile) {
            if (\in_array(pathinfo($cacheFile, PATHINFO_EXTENSION), $this->validImageExtensions, true)) {
                return $cacheFile;
            }
        }

        return null;
    }

    private function getHeader(string $path): string
    {
        $size = 0;

        foreach ($this->previewProviders as $provider) {
            $size = max($size, $provider->getFileHeaderSize());
        }

        return $size > 0 ? file_get_contents($path, false, null, 0, $size) : '';
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
