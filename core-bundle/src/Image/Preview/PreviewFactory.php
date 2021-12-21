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
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class PreviewFactory
{
    /**
     * @var iterable<int,PreviewProviderInterface>
     */
    private iterable $previewProviders;

    private ImageFactoryInterface $imageFactory;
    private PictureFactoryInterface $pictureFactory;
    private Studio $imageStudio;
    private string $cacheDir;
    private array $validImageExtensions;

    /**
     * @param iterable<int,PreviewProviderInterface> $previewProviders
     */
    public function __construct(iterable $previewProviders, ImageFactoryInterface $imageFactory, PictureFactoryInterface $pictureFactory, Studio $imageStudio, string $cacheDir, array $validImageExtensions)
    {
        $this->previewProviders = $previewProviders;
        $this->imageFactory = $imageFactory;
        $this->pictureFactory = $pictureFactory;
        $this->imageStudio = $imageStudio;
        $this->cacheDir = $cacheDir;
        $this->validImageExtensions = $validImageExtensions;
    }

    public function createPreviewFile(string $path, int $size = 0): string
    {
        // Supported image formats do not need an extra preview image
        if (\in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $this->validImageExtensions, true)) {
            return $path;
        }

        // Round up to the next highest power of two
        $size = (int) (2 ** log($size, 2));

        // Minimum size for previews is 512
        $size = max(512, $size);

        $cachePath = $this->createCachePath($path, $size);
        $globPattern = preg_replace('/[*?[{\\\\]/', '\\\\$0', $this->cacheDir.'/'.$cachePath).'.*';

        foreach (glob($globPattern) as $cacheFile) {
            if (\in_array(pathinfo($cacheFile, PATHINFO_EXTENSION), $this->validImageExtensions, true)) {
                return $cacheFile;
            }
        }

        $first1024Bytes = file_get_contents($path, false, null, 0, 1024);

        $provider = null;

        foreach ($this->previewProviders as $provider) {
            if ($provider->supports($path, $first1024Bytes)) {
                try {
                    $format = $provider->getImageFormat($path, $size, $first1024Bytes);
                    $targetPath = Path::join($this->cacheDir, "$cachePath.$format");

                    if (!is_dir(\dirname($targetPath))) {
                        (new Filesystem())->mkdir(\dirname($targetPath));
                    }

                    $provider->generatePreview($path, $size, $targetPath);

                    return $targetPath;
                } catch (\Throwable $exception) {
                    // Ignore
                }
            }
        }

        // TODO: throw custom exception
        throw new \RuntimeException('no provider able to preview');
    }

    /**
     * @param int|string|array|ResizeConfiguration|null $size
     */
    public function createPreviewImage(string $path, $size = null, ResizeOptions $options = null): ImageInterface
    {
        return $this->imageFactory
            ->create(
                $this->createPreviewFile($path, $this->getPreviewSizeFromImageSize($size)),
                $size,
                $options,
            )
        ;
    }

    /**
     * @param int|string|array|PictureConfiguration|null $size
     */
    public function createPreviewPicture(string $path, $size = null, ResizeOptions $options = null): PictureInterface
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

        $arguments = [$this->createPreviewFile($path, $this->getPreviewSizeFromImageSize($size)), $size];

        if (null !== $options && $canHandleResizeOptions($this->pictureFactory)) {
            $arguments[] = $options;
        }

        return $this->pictureFactory->create(...$arguments);
    }

    /**
     * @param int|string|array|PictureConfiguration|null $size
     */
    public function createPreviewFigure(string $path, $size = null, ResizeOptions $options = null): Figure
    {
        return $this->createPreviewFigureBuilder($path, $size, $options)->build();
    }

    /**
     * @param int|string|array|PictureConfiguration|null $size
     */
    public function createPreviewFigureBuilder(string $path, $size = null, ResizeOptions $options = null): FigureBuilder
    {
        return $this->imageStudio
            ->createFigureBuilder()
            ->fromPath($this->createPreviewFile($path, $this->getPreviewSizeFromImageSize($size)))
            ->setSize($size)
            ->setResizeOptions($options)
        ;
    }

    /**
     * @param int|string|array|PictureConfiguration|null $size
     */
    private function getPreviewSizeFromImageSize($size): int
    {
        // TODO: get correct size from size config
        return 1024;
    }

    private function createCachePath(string $path, int $size): string
    {
        $hashData = [
            Path::makeRelative($path, $this->cacheDir),
            (string) $size,
            (string) filemtime($path),
        ];

        $hash = substr(md5(implode('|', $hashData)), 0, 9);
        $pathinfo = pathinfo($path);

        return $hash[0].'/'.$pathinfo['filename'].'-'.substr($hash, 1);
    }
}
