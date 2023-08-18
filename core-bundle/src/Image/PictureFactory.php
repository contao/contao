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
use Contao\Image\PictureConfigurationItem;
use Contao\Image\PictureGeneratorInterface;
use Contao\Image\PictureInterface;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;
use Contao\ImageSizeItemModel;
use Contao\ImageSizeModel;
use Contao\StringUtil;

class PictureFactory implements PictureFactoryInterface
{
    private const ASPECT_RATIO_THRESHOLD = 0.05;

    private const FORMATS_ORDER = [
        'jxl' => 1,
        'avif' => 2,
        'heic' => 3,
        'webp' => 4,
        'png' => 5,
        'jpg' => 6,
        'jpeg' => 7,
        'gif' => 8,
    ];

    private array $imageSizeItemsCache = [];
    private string $defaultDensities = '';
    private array $predefinedSizes = [];
    private array $preserveMetadataFields;

    /**
     * @internal
     */
    public function __construct(
        private readonly PictureGeneratorInterface $pictureGenerator,
        private readonly ImageFactoryInterface $imageFactory,
        private readonly ContaoFramework $framework,
        private readonly bool $bypassCache,
        private readonly array $imagineOptions,
    ) {
        $this->preserveMetadataFields = (new ResizeOptions())->getPreserveCopyrightMetadata();
    }

    public function setDefaultDensities(string $densities): static
    {
        $this->defaultDensities = $densities;

        return $this;
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

    public function create(ImageInterface|string $path, PictureConfiguration|array|int|string|null $size = null, ResizeOptions|null $options = null): PictureInterface
    {
        $attributes = [];

        if ($path instanceof ImageInterface) {
            $image = $path;
        } else {
            $image = $this->imageFactory->create($path);
        }

        // Support arrays in a serialized form
        $size = StringUtil::deserialize($size);

        if (
            \is_array($size)
            && isset($size[2])
            && \is_string($size[2])
            && !isset($this->predefinedSizes[$size[2]])
            && 1 === substr_count($size[2], '_')
        ) {
            trigger_deprecation('contao/core-bundle', '5.0', 'Using the legacy resize mode "%s" has been deprecated and will no longer work in Contao 6.0.', $size[2]);

            $image->setImportantPart($this->imageFactory->getImportantPartFromLegacyMode($image, $size[2]));
            $size[2] = ResizeConfiguration::MODE_CROP;
        }

        if ($size instanceof PictureConfiguration) {
            $config = $size;

            $configOptions = new ResizeOptions();
            $configOptions->setPreserveCopyrightMetadata($this->preserveMetadataFields);
        } else {
            [$config, $attributes, $configOptions] = $this->createConfig($size);
        }

        // Always prefer options passed to this function
        $options ??= $configOptions;

        if (!$options->getImagineOptions()) {
            $options->setImagineOptions($this->imagineOptions);
        }

        $options->setBypassCache($options->getBypassCache() || $this->bypassCache);

        $picture = $this->pictureGenerator->generate($image, $config, $options);

        $attributes['hasSingleAspectRatio'] = $this->hasSingleAspectRatio($picture);

        return $this->addImageAttributes($picture, $attributes);
    }

    /**
     * Creates a picture configuration.
     *
     * @phpstan-return array{0:PictureConfiguration, 1:array<string, string>, 2:ResizeOptions}
     */
    private function createConfig(array|int|string|null $size): array
    {
        if (!\is_array($size)) {
            $size = [0, 0, $size];
        }

        $options = new ResizeOptions();
        $options->setPreserveCopyrightMetadata($this->preserveMetadataFields);

        $config = new PictureConfiguration();
        $attributes = [];

        if (isset($size[2])) {
            // Database record
            if (is_numeric($size[2])) {
                $imageSizeModel = $this->framework->getAdapter(ImageSizeModel::class);
                $imageSizes = $imageSizeModel->findByPk($size[2]);

                $config->setSize($this->createConfigItem($imageSizes?->row()));

                if (null !== $imageSizes) {
                    $options->setSkipIfDimensionsMatch((bool) $imageSizes->skipIfDimensionsMatch);

                    if ('delete' === $imageSizes->preserveMetadata) {
                        $options->setPreserveCopyrightMetadata([]);
                    } elseif (
                        'overwrite' === $imageSizes->preserveMetadata
                        && ($metadataFields = StringUtil::deserialize($imageSizes->preserveMetadataFields, true))
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

                    if ($quality = max(0, min(100, (int) $imageSizes->imageQuality))) {
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

                    $formats = [];

                    if ('' !== $imageSizes->formats) {
                        $formatsString = implode(';', StringUtil::deserialize($imageSizes->formats, true));

                        foreach (explode(';', $formatsString) as $format) {
                            [$source, $targets] = explode(':', $format, 2);
                            $targets = explode(',', $targets);

                            if (!isset($formats[$source])) {
                                $formats[$source] = $targets;
                                continue;
                            }

                            $formats[$source] = array_unique([...$formats[$source], ...$targets]);

                            usort(
                                $formats[$source],
                                static fn ($a, $b) => (self::FORMATS_ORDER[$a] ?? $a) <=> (self::FORMATS_ORDER[$b] ?? $b)
                            );
                        }
                    }

                    $config->setFormats($formats);
                }

                if ($imageSizes) {
                    if ($imageSizes->cssClass) {
                        $attributes['class'] = $imageSizes->cssClass;
                    }

                    if ($imageSizes->lazyLoading) {
                        $attributes['loading'] = 'lazy';
                    }
                }

                if (!\array_key_exists($size[2], $this->imageSizeItemsCache)) {
                    $adapter = $this->framework->getAdapter(ImageSizeItemModel::class);
                    $this->imageSizeItemsCache[$size[2]] = $adapter->findVisibleByPid($size[2], ['order' => 'sorting ASC']);
                }

                /** @var array<ImageSizeItemModel> $imageSizeItems */
                $imageSizeItems = $this->imageSizeItemsCache[$size[2]];

                if (null !== $imageSizeItems) {
                    $configItems = [];

                    foreach ($imageSizeItems as $imageSizeItem) {
                        $configItems[] = $this->createConfigItem($imageSizeItem->row());
                    }

                    $config->setSizeItems($configItems);
                }

                return [$config, $attributes, $options];
            }

            // Predefined size
            if (isset($this->predefinedSizes[$size[2]])) {
                $imageSizes = $this->predefinedSizes[$size[2]];

                $config->setSize($this->createConfigItem($imageSizes));
                $config->setFormats($imageSizes['formats'] ?? []);
                $options->setSkipIfDimensionsMatch($imageSizes['skipIfDimensionsMatch'] ?? false);

                $options->setPreserveCopyrightMetadata([
                    ...$options->getPreserveCopyrightMetadata(),
                    ...$imageSizes['preserveMetadataFields'] ?? [],
                ]);

                if (!empty($imageSizes['imagineOptions'])) {
                    $options->setImagineOptions([
                        ...$this->imagineOptions,
                        ...$imageSizes['imagineOptions'],
                    ]);
                }

                if (!empty($imageSizes['cssClass'])) {
                    $attributes['class'] = $imageSizes['cssClass'];
                }

                if (!empty($imageSizes['lazyLoading'])) {
                    $attributes['loading'] = 'lazy';
                }

                if (\count($imageSizes['items']) > 0) {
                    $configItems = [];

                    foreach ($imageSizes['items'] as $imageSizeItem) {
                        $configItems[] = $this->createConfigItem($imageSizeItem);
                    }

                    $config->setSizeItems($configItems);
                }

                return [$config, $attributes, $options];
            }
        }

        $resizeConfig = new ResizeConfiguration();

        if (!empty($size[0])) {
            $resizeConfig->setWidth((int) $size[0]);
        }

        if (!empty($size[1])) {
            $resizeConfig->setHeight((int) $size[1]);
        }

        if (!empty($size[2])) {
            $resizeConfig->setMode($size[2]);
        }

        if ($resizeConfig->isEmpty()) {
            $options->setSkipIfDimensionsMatch(true);
        }

        $configItem = new PictureConfigurationItem();
        $configItem->setResizeConfig($resizeConfig);

        if ($this->defaultDensities) {
            $configItem->setDensities($this->defaultDensities);
        }

        $config->setSize($configItem);

        return [$config, $attributes, $options];
    }

    /**
     * Creates a picture configuration item.
     */
    private function createConfigItem(array|null $imageSize = null): PictureConfigurationItem
    {
        $configItem = new PictureConfigurationItem();
        $resizeConfig = new ResizeConfiguration();

        if (null !== $imageSize) {
            if (isset($imageSize['width'])) {
                $resizeConfig->setWidth((int) $imageSize['width']);
            }

            if (isset($imageSize['height'])) {
                $resizeConfig->setHeight((int) $imageSize['height']);
            }

            if (isset($imageSize['zoom'])) {
                $resizeConfig->setZoomLevel((int) $imageSize['zoom']);
            }

            if (isset($imageSize['resizeMode'])) {
                $resizeConfig->setMode((string) $imageSize['resizeMode']);
            }

            $configItem->setResizeConfig($resizeConfig);

            if (isset($imageSize['sizes'])) {
                $configItem->setSizes((string) $imageSize['sizes']);
            }

            if (isset($imageSize['densities'])) {
                $configItem->setDensities((string) $imageSize['densities']);
            }

            if (isset($imageSize['media'])) {
                $configItem->setMedia((string) $imageSize['media']);
            }
        }

        return $configItem;
    }

    private function addImageAttributes(PictureInterface $picture, array $attributes): PictureInterface
    {
        if (!$attributes) {
            return $picture;
        }

        $img = $picture->getRawImg();

        foreach ($attributes as $attribute => $value) {
            $img[$attribute] = $value;
        }

        return new Picture($img, $picture->getRawSources());
    }

    /**
     * Returns true if the aspect ratios of all sources of the picture are
     * nearly the same and differ less than the ASPECT_RATIO_THRESHOLD.
     */
    private function hasSingleAspectRatio(PictureInterface $picture): bool
    {
        if (!$picture->getRawSources()) {
            return true;
        }

        $img = $picture->getRawImg();

        if (empty($img['width']) || empty($img['height'])) {
            return false;
        }

        foreach ($picture->getRawSources() as $source) {
            if (empty($source['width']) || empty($source['height'])) {
                return false;
            }

            $diffA = abs($img['width'] / $img['height'] / ($source['width'] / $source['height']) - 1);
            $diffB = abs($img['height'] / $img['width'] / ($source['height'] / $source['width']) - 1);

            if ($diffA > self::ASPECT_RATIO_THRESHOLD && $diffB > self::ASPECT_RATIO_THRESHOLD) {
                return false;
            }
        }

        return true;
    }
}
