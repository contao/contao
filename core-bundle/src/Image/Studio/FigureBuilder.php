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

use Contao\Controller;
use Contao\CoreBundle\File\MetaData;
use Contao\CoreBundle\Framework\Adapter;
use Contao\FilesModel;
use Contao\Image\PictureConfiguration;
use Contao\PageModel;
use Contao\Validator;
use Psr\Container\ContainerInterface;
use Webmozart\PathUtil\Path;

/**
 * Use the `FigureBuilder` to easily create Figure result objects by applying
 * configuration via a fluent interface. You can call `build()` multiple times
 * (and change some settings in between) to create multiple instances.
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
     * The resource's absolute file path.
     *
     * @var string|null
     */
    private $filePath;

    /**
     * The resource's file model (if applicable).
     *
     * @var FilesModel|null
     */
    private $filesModel;

    /**
     * User defined size configuration.
     *
     * @var mixed|null
     */
    private $sizeConfiguration;

    /**
     * User defined custom locale (overwriting the default).
     *
     * @var string|null
     */
    private $locale;

    /**
     * User defined meta data (overwriting the default).
     *
     * @var MetaData|null
     */
    private $metaData;

    /**
     * User defined link attributes (adding to or overwriting the default attributes).
     *
     * @var array<string, string|null>
     */
    private $additionalLinkAttributes = [];

    /**
     * User defined light box uri (overwriting the default).
     *
     * @var string|null
     */
    private $lightBoxUri;

    /**
     * User defined light box size configuration (overwriting the default).
     *
     * @var mixed|null
     */
    private $lightBoxSizeConfiguration;

    /**
     * User defined light box group identifier (overwriting the default).
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
     * @internal Use the `contao.image.studio` factory to get an instance of this class.
     */
    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Set the image resource from a FilesModel.
     */
    public function fromFilesModel(FilesModel $filesModel): self
    {
        if ('file' !== $filesModel->type) {
            throw new \InvalidArgumentException("DBAFS item '{$filesModel->path}' is not a file.");
        }

        $this->filePath = Path::makeAbsolute($filesModel->path, $this->projectDir());
        $this->filesModel = $filesModel;

        return $this;
    }

    /**
     * Set the image resource from a tl_files uuid.
     */
    public function fromUuid(string $uuid): self
    {
        $filesModel = $this->filesModelAdapter()->findByUuid($uuid);

        if (null === $filesModel) {
            throw new \InvalidArgumentException("DBAFS item with uuid '$uuid' could not be found.");
        }

        return $this->fromFilesModel($filesModel);
    }

    /**
     * Set the image resource from a tl_files id.
     */
    public function fromId(int $id): self
    {
        $filesModel = $this->filesModelAdapter()->findByPk($id);

        if (null === $filesModel) {
            throw new \InvalidArgumentException("DBAFS item with id '$id' could not be found.");
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
        $isInsideUploadPath = function (string $path): bool {
            $uploadPath = $this->locator
                ->get('parameter_bag')
                ->get('contao.upload_path')
            ;

            return 0 === strncmp($path, $uploadPath.'/', \strlen($uploadPath) + 1);
        };

        if ($autoDetectDbafsPaths && $isInsideUploadPath($path)) {
            // todo: Do we also want to support absolute paths to DBAFS resources?
            $filesModel = $this->filesModelAdapter()->findByPath($path);

            if (null !== $filesModel) {
                return $this->fromFilesModel($filesModel);
            }
        }

        $this->filePath = Path::isAbsolute($path) ?
            Path::canonicalize($path) :
            Path::makeAbsolute($path, $this->projectDir());

        return $this;
    }

    /**
     * Set the image resource by guessing the identifier type.
     *
     * @param string|FilesModel $identifier Can be a FilesModel, a tl_files uuid/id/path or a file system path
     */
    public function from($identifier): self
    {
        if ($identifier instanceof FilesModel) {
            return $this->fromFilesModel($identifier);
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
     * data from the FilesModel (if available) will be used instead.
     */
    public function setMetaData(?MetaData $metaData): self
    {
        $this->metaData = $metaData;

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
     * want to explicitly remove the value (including auto generated defaults)
     * set the `$forceRemove` flag to true.
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
     * Set the link href attribute. Set the value to null to use the auto
     * generated default.
     */
    public function setLinkHref(?string $uri): self
    {
        $this->setLinkAttribute('href', $uri);

        return $this;
    }

    /**
     * Set a custom light box uri. By default or if the argument is set to
     * null the uri will be automatically determined from the meta data
     * or base resource.
     *
     * For this setting to take effect make sure you enabled the creation of a
     * light box by calling `enableLightBox()`.
     */
    public function setLightBoxUri(?string $uri): self
    {
        $this->lightBoxUri = $uri;

        return $this;
    }

    /**
     * Set a size configuration that will be applied to the light box image (if
     * available).
     *
     * For this setting to take effect make sure you enabled the creation of a
     * light box by calling `enableLightBox()`.
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
     * light box by calling `enableLightBox()`.
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
            ->get('contao.image.studio')
            ->createImage($settings->filePath, $this->sizeConfiguration)
        ;

        // We're defining some values via a Closure to make their evaluation lazy
        return new Figure(
            $imageResult,
            function (Figure $figure) use ($settings): ?MetaData {
                return $this->onDefineMetaData($settings);
            },
            $settings->additionalLinkAttributes,
            function (Figure $figure) use ($settings): ?LightBoxResult {
                return $this->onDefineLightBoxResult($settings, $figure);
            }
        );
    }

    /**
     * Define meta data [on demand].
     */
    private function onDefineMetaData(self $settings): ?MetaData
    {
        if (null !== $settings->metaData) {
            return $settings->metaData;
        }

        if (null === $settings->filesModel) {
            return null;
        }

        // Get fallback locale list or use without fallbacks if explicitly set
        $locales = null !== $settings->locale ? [$settings->locale] : $this->getFallbackLocaleList();

        return $settings->filesModel->getMetaData(...$locales);
    }

    /**
     * Define the secondary image result based on the source setting [on demand].
     */
    private function onDefineLightBoxResult(self $settings, Figure $result): ?LightBoxResult
    {
        if (!$settings->enableLightBox) {
            return null;
        }

        // Use explicitly set uri (1), fall back to using meta data (2) or use the base resource (3) if empty.
        $lightBoxUri = $settings->lightBoxUri ?? Controller::replaceInsertTags($result->getMetaData()->getUrl()) ?: $settings->filePath;

        return $this->locator
            ->get('contao.image.studio')
            ->createLightBoxImage($lightBoxUri, $settings->lightBoxSizeConfiguration, $this->lightBoxGroupIdentifier)
        ;
    }

    /**
     * @return FilesModel&Adapter
     */
    private function filesModelAdapter()
    {
        $framework = $this->locator->get('contao.framework');
        $framework->initialize();

        return $framework->getAdapter(FilesModel::class);
    }

    /**
     * @return Validator&Adapter
     */
    private function validatorAdapter(): Validator
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

    /**
     * Return a list of locales (if available) in the following order:
     *  1. language of current page,
     *  2. root page fallback language,
     *  3. request locale,
     *  4. system locale.
     */
    private function getFallbackLocaleList(): array
    {
        $locales = [
            $this->locator
                ->get('parameter_bag')
                ->get('kernel.default_locale'),
        ];

        $request = $this->locator
            ->get('request_stack')
            ->getCurrentRequest()
        ;

        if (null === $request) {
            return $locales;
        }

        array_unshift($locales, $request->getLocale());

        if ($request->attributes->has('pageModel')) {
            /** @var PageModel $page */
            $page = $request->attributes->get('pageModel');

            foreach ([$page->rootFallbackLanguage, $page->language] as $value) {
                if (!empty($value)) {
                    array_unshift($locales, str_replace('-', '_', $page->language));
                }
            }
        }

        // only keep first occurrences
        return array_unique($locales);
    }
}
