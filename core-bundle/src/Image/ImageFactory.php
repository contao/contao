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
use Contao\Image\DeferredResizerInterface;
use Contao\Image\Exception\CoordinatesOutOfBoundsException;
use Contao\Image\Image;
use Contao\Image\ImageInterface;
use Contao\Image\ImportantPart;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\Image\ResizerInterface;
use Contao\ImageSizeModel;
use Contao\StringUtil;
use Imagine\Image\ImagineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ImageFactory implements ImageFactoryInterface
{
    private array $predefinedSizes = [];
    private array $preserveMetadataFields;

    /**
     * @internal
     */
    public function __construct(
        private readonly ResizerInterface $resizer,
        private readonly ImagineInterface $imagine,
        private readonly ImagineInterface $imagineSvg,
        private readonly Filesystem $filesystem,
        private readonly ContaoFramework $framework,
        private readonly bool $bypassCache,
        private readonly array $imagineOptions,
        private readonly array $validExtensions,
        private readonly string $uploadDir,
    ) {
        $this->preserveMetadataFields = (new ResizeOptions())->getPreserveCopyrightMetadata();
    }

    /**
     * Sets the predefined image sizes.
     */
    public function setPredefinedSizes(array $predefinedSizes): void
    {
        $this->predefinedSizes = $predefinedSizes;
    }

    public function setPreserveMetadataFields(array $preserveMetadataFields): void
    {
        $this->preserveMetadataFields = $preserveMetadataFields;
    }

    public function create($path, ResizeConfiguration|array|int|string|null $size = null, $options = null): ImageInterface
    {
        if (null !== $options && !\is_string($options) && !$options instanceof ResizeOptions) {
            throw new \InvalidArgumentException('Options must be of type null, string or '.ResizeOptions::class);
        }

        if ($path instanceof ImageInterface) {
            $image = $path;
        } else {
            $path = (string) $path;
            $fileExtension = Path::getExtension($path, true);

            if (\in_array($fileExtension, ['svg', 'svgz'], true)) {
                $imagine = $this->imagineSvg;
            } else {
                $imagine = $this->imagine;
            }

            if (!\in_array($fileExtension, $this->validExtensions, true)) {
                throw new \InvalidArgumentException(sprintf('Image type "%s" was not allowed to be processed', $fileExtension));
            }

            if (!Path::isAbsolute($path)) {
                throw new \InvalidArgumentException(sprintf('Image path "%s" must be absolute', $path));
            }

            if (
                $this->resizer instanceof DeferredResizerInterface
                && !$this->filesystem->exists($path)
                && $deferredImage = $this->resizer->getDeferredImage($path, $imagine)
            ) {
                $image = $deferredImage;
            } else {
                $image = new Image($path, $imagine, $this->filesystem);
            }
        }

        $targetPath = $options instanceof ResizeOptions ? $options->getTargetPath() : $options;

        // Support arrays in a serialized form
        $size = StringUtil::deserialize($size);

        if ($size instanceof ResizeConfiguration) {
            $resizeConfig = $size;
            $importantPart = null;
        } else {
            [$resizeConfig, $importantPart, $options] = $this->createConfig($size, $image);
        }

        if (!\is_object($path) || !$path instanceof ImageInterface) {
            if (null === $importantPart) {
                try {
                    $importantPart = $this->createImportantPart($image);
                } catch (CoordinatesOutOfBoundsException $exception) {
                    throw new CoordinatesOutOfBoundsException(sprintf('%s for file "%s"', $exception->getMessage(), $path), $exception->getCode(), $exception);
                }
            }

            $image->setImportantPart($importantPart);
        }

        if (null === $options && null === $targetPath && null === $size) {
            return $image;
        }

        if (!$options instanceof ResizeOptions) {
            $options = new ResizeOptions();

            if (!$size instanceof ResizeConfiguration && $resizeConfig->isEmpty()) {
                $options->setSkipIfDimensionsMatch(true);
            }
        }

        if (null !== $targetPath) {
            $options->setTargetPath($targetPath);
        }

        if (!$options->getImagineOptions()) {
            $options->setImagineOptions($this->imagineOptions);
        }

        $options->setBypassCache($options->getBypassCache() || $this->bypassCache);

        return $this->resizer->resize($image, $resizeConfig, $options);
    }

    public function getImportantPartFromLegacyMode(ImageInterface $image, $mode): ImportantPart
    {
        if (1 !== substr_count($mode, '_')) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a legacy resize mode', $mode));
        }

        $importantPart = [0, 0, 1, 1];
        [$modeX, $modeY] = explode('_', $mode);

        if ('left' === $modeX) {
            $importantPart[2] = 0;
        } elseif ('right' === $modeX) {
            $importantPart[0] = 1;
            $importantPart[2] = 0;
        }

        if ('top' === $modeY) {
            $importantPart[3] = 0;
        } elseif ('bottom' === $modeY) {
            $importantPart[1] = 1;
            $importantPart[3] = 0;
        }

        return new ImportantPart($importantPart[0], $importantPart[1], $importantPart[2], $importantPart[3]);
    }

    /**
     * Creates a resize configuration object.
     *
     * @param int|array|null $size An image size or an array with width, height and resize mode
     *
     * @return array<(ResizeConfiguration|ImportantPart|ResizeOptions|null)>
     */
    private function createConfig(array|int|null $size, ImageInterface $image): array
    {
        if (!\is_array($size)) {
            $size = [0, 0, $size];
        }

        $config = new ResizeConfiguration();

        $options = new ResizeOptions();
        $options->setPreserveCopyrightMetadata($this->preserveMetadataFields);

        if (isset($size[2])) {
            // Database record
            if (is_numeric($size[2])) {
                $imageModel = $this->framework->getAdapter(ImageSizeModel::class);

                if ($imageSize = $imageModel->findByPk($size[2])) {
                    $this->enhanceResizeConfig($config, $imageSize->row());
                    $options->setSkipIfDimensionsMatch((bool) $imageSize->skipIfDimensionsMatch);

                    if ('delete' === $imageSize->preserveMetadata) {
                        $options->setPreserveCopyrightMetadata([]);
                    } elseif (
                        'overwrite' === $imageSize->preserveMetadata
                        && ($metadataFields = StringUtil::deserialize($imageSize->preserveMetadataFields, true))
                    ) {
                        $options->setPreserveCopyrightMetadata(
                            array_merge_recursive(
                                ...array_map(
                                    static fn ($metadata) => StringUtil::deserialize($metadata, true),
                                    $metadataFields,
                                ),
                            ),
                        );
                    }

                    if ($quality = max(0, min(100, (int) $imageSize->imageQuality))) {
                        $options->setImagineOptions([
                            ...$this->imagineOptions,
                            'quality' => $quality,
                            'jpeg_quality' => $quality,
                            'webp_quality' => $quality,
                            'avif_quality' => $quality,
                            'heic_quality' => $quality,
                            'jxl_quality' => $quality,
                        ]);

                        if (100 === $quality) {
                            $options->setImagineOptions([
                                ...$options->getImagineOptions(),
                                'webp_lossless' => true,
                                'avif_lossless' => true,
                                'heic_lossless' => true,
                                'jxl_lossless' => true,
                            ]);
                        }
                    }
                }

                return [$config, null, $options];
            }

            // Predefined sizes
            if (isset($this->predefinedSizes[$size[2]])) {
                $this->enhanceResizeConfig($config, $this->predefinedSizes[$size[2]]);
                $options->setSkipIfDimensionsMatch($this->predefinedSizes[$size[2]]['skipIfDimensionsMatch'] ?? false);

                $options->setPreserveCopyrightMetadata([
                    ...$options->getPreserveCopyrightMetadata(),
                    ...$this->predefinedSizes[$size[2]]['preserveMetadataFields'] ?? [],
                ]);

                if (!empty($this->predefinedSizes[$size[2]]['imagineOptions'])) {
                    $options->setImagineOptions([
                        ...$this->imagineOptions,
                        ...$this->predefinedSizes[$size[2]]['imagineOptions'],
                    ]);
                }

                return [$config, null, $options];
            }
        }

        if (!empty($size[0])) {
            $config->setWidth((int) $size[0]);
        }

        if (!empty($size[1])) {
            $config->setHeight((int) $size[1]);
        }

        if (!isset($size[2]) || 1 !== substr_count($size[2], '_')) {
            if (!empty($size[2])) {
                $config->setMode($size[2]);
            }

            return [$config, null, null];
        }

        trigger_deprecation('contao/core-bundle', '5.0', 'Using the legacy resize mode "%s" has been deprecated and will no longer work in Contao 6.0.', $size[2]);

        $config->setMode(ResizeConfiguration::MODE_CROP);

        return [$config, $this->getImportantPartFromLegacyMode($image, $size[2]), null];
    }

    /**
     * Enhances the resize configuration with the image size settings.
     */
    private function enhanceResizeConfig(ResizeConfiguration $config, array $imageSize): void
    {
        if (isset($imageSize['width'])) {
            $config->setWidth((int) $imageSize['width']);
        }

        if (isset($imageSize['height'])) {
            $config->setHeight((int) $imageSize['height']);
        }

        if (isset($imageSize['zoom'])) {
            $config->setZoomLevel((int) $imageSize['zoom']);
        }

        if (isset($imageSize['resizeMode'])) {
            $config->setMode((string) $imageSize['resizeMode']);
        }
    }

    /**
     * Fetches the important part from the database.
     */
    private function createImportantPart(ImageInterface $image): ImportantPart|null
    {
        if (!Path::isBasePath($this->uploadDir, $image->getPath())) {
            return null;
        }

        if (!$this->framework->isInitialized()) {
            throw new \RuntimeException('Contao framework was not initialized');
        }

        $filesModel = $this->framework->getAdapter(FilesModel::class);
        $file = $filesModel->findByPath($image->getPath());

        if (!$file || !$file->importantPartWidth || !$file->importantPartHeight) {
            return null;
        }

        return new ImportantPart(
            (float) $file->importantPartX,
            (float) $file->importantPartY,
            (float) $file->importantPartWidth,
            (float) $file->importantPartHeight
        );
    }
}
