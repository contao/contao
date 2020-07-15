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

use Contao\CoreBundle\Exception\InvalidResourceException;
use Contao\CoreBundle\File\MetaData;
use Contao\FilesModel;
use Contao\Image\ImageInterface;
use Contao\Image\PictureConfiguration;
use Contao\PageModel;
use Contao\Validator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Use the FigureBuilder to create Figure result objects. The class features a
 * fluent interface to configure the desired output. When you're ready call
 * build() to get a Figure. If you need another instance with similar settings,
 * you can alter values and call build() again - this won't affect your first
 * instance.
 */
class FigureBuilder
{
    /**
     * @readonly
     *
     * @var ContainerInterface
     */
    private $locator;

    /**
     * @readonly
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The resource's absolute file path.
     *
     * @var string|null
     */
    private $filePath;

    /**
     * The resource's file model if applicable.
     *
     * @var FilesModel|null
     */
    private $filesModel;

    /**
     * User defined size configuration.
     *
     * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements
     *
     * @var mixed|null
     */
    private $sizeConfiguration;

    /**
     * User defined custom locale. This will overwrite the default if set.
     *
     * @var string|null
     */
    private $locale;

    /**
     * User defined meta data. This will overwrite the default if set.
     *
     * @var MetaData|null
     */
    private $metaData;

    /**
     * Determines if a meta data should never be present in the output.
     *
     * @var bool
     */
    private $disableMetaData;

    /**
     * User defined link attributes. These will add to or overwrite the default values.
     *
     * @var array<string, string|null>
     */
    private $additionalLinkAttributes = [];

    /**
     * User defined light box resource or url. This will overwrite the default if set.
     *
     * @var string|ImageInterface|null
     */
    private $lightBoxResourceOrUrl;

    /**
     * User defined light box size configuration. This will overwrite the default if set.
     *
     * @var mixed|null
     */
    private $lightBoxSizeConfiguration;

    /**
     * User defined light box group identifier. This will overwrite the default if set.
     *
     * @var string|null
     */
    private $lightBoxGroupIdentifier;

    /**
     * Determines if a light box (or "fullsize") image should be created.
     *
     * @var bool
     */
    private $enableLightBox;

    /**
     * User defined template options.
     *
     * @var array<string, mixed>
     */
    private $options = [];

    /**
     * @internal use the Contao\Image\Studio\Studio factory to get an instance of this class
     */
    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;

        $this->filesystem = new Filesystem();
    }

    /**
     * Set the image resource from a FilesModel.
     */
    public function fromFilesModel(FilesModel $filesModel): self
    {
        if ('file' !== $filesModel->type) {
            throw new InvalidResourceException("DBAFS item '{$filesModel->path}' is not a file.");
        }

        $this->filePath = Path::makeAbsolute($filesModel->path, $this->projectDir());
        $this->filesModel = $filesModel;

        if (!$this->filesystem->exists($this->filePath)) {
            throw new InvalidResourceException("No resource could be located at path '{$this->filePath}'.");
        }

        return $this;
    }

    /**
     * Set the image resource from a tl_files UUID.
     */
    public function fromUuid(string $uuid): self
    {
        $filesModel = $this->filesModelAdapter()->findByUuid($uuid);

        if (null === $filesModel) {
            throw new InvalidResourceException("DBAFS item with UUID '$uuid' could not be found.");
        }

        return $this->fromFilesModel($filesModel);
    }

    /**
     * Set the image resource from a tl_files ID.
     */
    public function fromId(int $id): self
    {
        $filesModel = $this->filesModelAdapter()->findByPk($id);

        if (null === $filesModel) {
            throw new InvalidResourceException("DBAFS item with ID '$id' could not be found.");
        }

        return $this->fromFilesModel($filesModel);
    }

    /**
     * Set the image resource from an absolute or relative path.
     *
     * @param bool $autoDetectDbafsPaths Set to false to skip searching for a FilesModel
     */
    public function fromPath(string $path, bool $autoDetectDbafsPaths = true): self
    {
        $projectDir = $this->projectDir();

        // Make sure path is absolute and in a canonical form
        $path = Path::isAbsolute($path) ? Path::canonicalize($path) : Path::makeAbsolute($path, $projectDir);

        // Only check for a FilesModel if requested if resource is inside upload path
        if ($autoDetectDbafsPaths && Path::isBasePath(Path::join($projectDir, $this->uploadPath()), $path)) {
            $filesModel = $this->filesModelAdapter()->findByPath($path);

            if (null !== $filesModel) {
                return $this->fromFilesModel($filesModel);
            }
        }

        $this->filePath = $path;
        $this->filesModel = null;

        if (!$this->filesystem->exists($this->filePath)) {
            throw new InvalidResourceException("No resource could be located at path '{$this->filePath}'.");
        }

        return $this;
    }

    /**
     * Set the image resource from an ImageInterface.
     */
    public function fromImage(ImageInterface $image): self
    {
        return $this->fromPath($image->getPath());
    }

    /**
     * Set the image resource by guessing the identifier type.
     *
     * @param int|string|FilesModel|ImageInterface $identifier Can be a FilesModel, an ImageInterface, a tl_files uuid/id/path or a file system path
     */
    public function from($identifier): self
    {
        if ($identifier instanceof FilesModel) {
            return $this->fromFilesModel($identifier);
        }

        if ($identifier instanceof ImageInterface) {
            return $this->fromImage($identifier);
        }

        if ($this->validatorAdapter()->isUuid($identifier)) {
            return $this->fromUuid($identifier);
        }

        if (is_numeric($identifier)) {
            return $this->fromId($identifier);
        }

        return $this->fromPath($identifier);
    }

    /**
     * Set a size configuration that will be applied to the resource.
     *
     * @param int|string|array|PictureConfiguration $size A picture size configuration or reference
     */
    public function setSize($size): self
    {
        $this->sizeConfiguration = $size;

        return $this;
    }

    /**
     * Set custom meta data. By default or if the argument is set to null, meta
     * data is trying to be pulled from the FilesModel.
     */
    public function setMetaData(?MetaData $metaData): self
    {
        $this->metaData = $metaData;

        return $this;
    }

    /**
     * Disable/allow creation of meta data in the output even if it is present.
     * This setting is not active by default (= meta data will be created if
     * possible).
     */
    public function disableMetaData(bool $disable = true): self
    {
        $this->disableMetaData = $disable;

        return $this;
    }

    /**
     * Set a custom locale. By default or if the argument is set to null, the
     * locale is determined from the request context and/or system settings.
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Add a custom link attribute. Set the value to null to remove it. If you
     * want to explicitly remove an auto generated value from the results, set
     * the $forceRemove flag to true.
     */
    public function setLinkAttribute(string $attribute, ?string $value, $forceRemove = false): self
    {
        if (null !== $value || $forceRemove) {
            $this->additionalLinkAttributes[$attribute] = $value;
        } else {
            unset($this->additionalLinkAttributes[$attribute]);
        }

        return $this;
    }

    /**
     * Set all custom link attributes as an associative array. This will
     * overwrite previously set attributes. If you want to explicitly remove an
     * auto generated value from the results, set the respective attribute to
     * null.
     */
    public function setLinkAttributes(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (!\is_string($key) || !\is_string($value)) {
                throw new \InvalidArgumentException('Link attributes must be an array of type <string, string>.');
            }
        }

        $this->additionalLinkAttributes = $attributes;

        return $this;
    }

    /**
     * Set the link href attribute. Set the value to null to use the auto
     * generated default.
     */
    public function setLinkHref(?string $url): self
    {
        $this->setLinkAttribute('href', $url);

        return $this;
    }

    /**
     * Set a custom light box resource (file path or ImageInterface) or url.
     * By default or if the argument is set to null the image/target will be
     * automatically determined from the meta data or base resource.
     *
     * For this setting to take effect make sure you enabled the creation of a
     * light box by calling enableLightBox().
     *
     * @param string|ImageInterface|null $resourceOrUrl
     */
    public function setLightBoxResourceOrUrl($resourceOrUrl): self
    {
        $this->lightBoxResourceOrUrl = $resourceOrUrl;

        return $this;
    }

    /**
     * Set a size configuration that will be applied to the light box image.
     *
     * For this setting to take effect make sure you enabled the creation of a
     * light box by calling enableLightBox().
     *
     * @param int|string|array|PictureConfiguration $size A picture size configuration or reference
     */
    public function setLightBoxSize($size): self
    {
        $this->lightBoxSizeConfiguration = $size;

        return $this;
    }

    /**
     * Set a custom light box group id. By default or if the argument is set to
     * null an id will be generated.
     *
     * For this setting to take effect make sure you enabled the creation of a
     * light box by calling enableLightBox().
     */
    public function setLightBoxGroupIdentifier(?string $identifier): self
    {
        $this->lightBoxGroupIdentifier = $identifier;

        return $this;
    }

    /**
     * Enable/disable creation of a light box image (if possible) and/or
     * outputting the respective link attributes. This setting is disabled by
     * default.
     */
    public function enableLightBox(bool $enable = true): self
    {
        $this->enableLightBox = $enable;

        return $this;
    }

    /**
     * Set all template options as an associative array.
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Create a result object with the current settings.
     */
    public function build(): Figure
    {
        if (null === $this->filePath) {
            throw new \LogicException('You need to set a resource before building the result.');
        }

        // Freeze settings to allow reusing this builder object.
        $settings = clone $this;

        $imageResult = $this->locator
            ->get(Studio::class)
            ->createImage($settings->filePath, $settings->sizeConfiguration)
        ;

        // We are defining some values via a Closure to make their evaluation lazy
        return new Figure(
            $imageResult,
            \Closure::bind(
                function (Figure $figure): ?MetaData {
                    return $this->onDefineMetaData();
                },
                $settings
            ),
            \Closure::bind(
                function (Figure $figure): array {
                    return $this->onDefineLinkAttributes($figure);
                },
                $settings
            ),
            \Closure::bind(
                function (Figure $figure): ?LightBoxResult {
                    return $this->onDefineLightBoxResult($figure);
                },
                $settings
            ),
            $this->options
        );
    }

    /**
     * Define meta data on demand.
     */
    private function onDefineMetaData(): ?MetaData
    {
        if ($this->disableMetaData) {
            return null;
        }

        if (null !== $this->metaData) {
            return $this->metaData;
        }

        if (null === $this->filesModel) {
            return null;
        }

        // Get fallback locale list or use without fallbacks if explicitly set
        $locales = null !== $this->locale ? [$this->locale] : $this->getFallbackLocaleList();
        $metaData = $this->filesModel->getMetaData(...$locales);

        if (null !== $metaData) {
            return $metaData;
        }

        // If no meta data can be obtained from the model, we create a
        // container from the default meta fields with empty values instead.
        $metaFields = $this->filesModelAdapter()->getMetaFields();

        return new MetaData(array_combine($metaFields, array_fill(0, \count($metaFields), '')));
    }

    /**
     * Define link attributes on demand.
     */
    private function onDefineLinkAttributes(Figure $result): array
    {
        $linkAttributes = [];

        // Open in a new window if light box was requested but is invalid (fullsize)
        if ($this->enableLightBox && !$result->hasLightBox()) {
            $linkAttributes['target'] = '_blank';
        }

        return array_merge($linkAttributes, $this->additionalLinkAttributes);
    }

    /**
     * Define the light box result if it is enabled on demand.
     */
    private function onDefineLightBoxResult(Figure $result): ?LightBoxResult
    {
        if (!$this->enableLightBox) {
            return null;
        }

        $getMetaDataUrl = static function () use ($result): ?string {
            if (!$result->hasMetaData()) {
                return null;
            }

            return $result->getMetaData()->getUrl() ?: null;
        };

        $getResourceOrUrl = function ($target): array {
            if ($target instanceof ImageInterface) {
                return [$target, null];
            }

            $validExtensions = $this->locator
                ->get('parameter_bag')
                ->get('contao.image.valid_extensions')
            ;

            $validExtension = \in_array(Path::getExtension($target), $validExtensions, true);
            $externalUrl = 1 === preg_match('#^https?://#', $target);

            if (!$validExtension) {
                return [null, null];
            }

            if ($externalUrl) {
                return [null, $target];
            }

            $projectDir = $this->locator
                ->get('parameter_bag')
                ->get('kernel.project_dir')
            ;

            $filePath = Path::isAbsolute($target) ?
                Path::canonicalize($target) :
                Path::makeAbsolute($target, $projectDir);

            if (!is_file($filePath)) {
                $filePath = null;
            }

            return [$filePath, null];
        };

        // Use explicitly set data (1), fall back to using meta data (2) or use the base resource (3) if empty.
        $lightBoxResourceOrUrl = $this->lightBoxResourceOrUrl ?? $getMetaDataUrl() ?? $this->filePath;

        [$filePathOrImage, $url] = $getResourceOrUrl($lightBoxResourceOrUrl);

        if (null === $filePathOrImage && null === $url) {
            return null;
        }

        return $this->locator
            ->get(Studio::class)
            ->createLightBoxImage($filePathOrImage, $url, $this->lightBoxSizeConfiguration, $this->lightBoxGroupIdentifier)
        ;
    }

    private function filesModelAdapter()
    {
        $framework = $this->locator->get('contao.framework');
        $framework->initialize();

        return $framework->getAdapter(FilesModel::class);
    }

    private function validatorAdapter()
    {
        $framework = $this->locator->get('contao.framework');
        $framework->initialize();

        return $framework->getAdapter(Validator::class);
    }

    private function projectDir(): string
    {
        return $this->locator
            ->get('parameter_bag')
            ->get('kernel.project_dir')
        ;
    }

    private function uploadPath(): string
    {
        return $this->locator
            ->get('parameter_bag')
            ->get('contao.upload_path')
        ;
    }

    /**
     * Return a list of locales (if available) in the following order:
     *  1. language of current page,
     *  2. root page fallback language.
     */
    private function getFallbackLocaleList(): array
    {
        $page = $GLOBALS['objPage'] ?? null;

        if (!$page instanceof PageModel) {
            return [];
        }

        $locales = [
            str_replace('-', '_', $page->language),
            str_replace('-', '_', $page->rootFallbackLanguage),
        ];

        return array_unique(array_filter($locales));
    }
}
