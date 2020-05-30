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

use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\ImageFactoryInterface;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\FilesModel;
use Contao\Image\ImageDimensions;
use Contao\Image\PictureConfiguration;
use Contao\Image\PictureInterface;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Validator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Webmozart\PathUtil\Path;

/**
 * This class is a helper for image based tasks (typically in the frontend). It
 * acts as a successor of `Controller::addImageToTemplate` and provides a lazily
 * evaluated way to process pictures and meta data and access the results in a
 * structured way.
 *
 * Usage: A new instance of this class is intended to be used for each job,
 *        although reusing configuration and issuing multiple calls is a
 *        supported use case. A factory is provided for conveniently creating
 *        new instances on the fly.
 *
 *        Use the Studio's fluent interface to set a resource, configuration and
 *        meta data. Then trigger the processing by accessing the picture, meta
 *        data or template data.
 *
 * Example:
 *
 *        // Create image from path, apply size configuration, overwrite meta data
 *        $studio
 *           ->from('my/resource.jpg')
 *           ->setSize('_sizeA')
 *           ->setMetaData($metaData);
 *
 *        // Get sources and template data
 *        $sources = $studio->getSources();
 *        $data = $studio->getTemplateData();
 *
 *        // Create another size variant with the same resource/meta data
 *        $sourcesB = $studio
 *           ->setSize('_sizeB')
 *           ->getSources();
 */
final class Studio implements ServiceSubscriberInterface
{
    private const CACHE_PICTURE = 'picture';
    private const CACHE_META_DATA = 'metaData';
    private const CACHE_TEMPLATE_DATA = 'templateData';
    private const CACHE_ORIGINAL_DIMENSIONS = 'dimensions';

    /**
     * @readonly
     *
     * @var ContainerInterface
     */
    private $locator;

    /**
     * Simple internal key-value cache to allow cheap subsequent value access.
     *
     * @var array<string, mixed>
     */
    private $cache = [];

    /**
     * The resource's file path.
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
     * User defined custom locale.
     *
     * @var string|null
     */
    private $locale;

    /**
     * User defined meta data.
     *
     * @var MetaData|null
     */
    private $metaData;

    /**
     * Determines if a secondary image (e.g. light box) will be created (if applicable).
     *
     * @var bool
     */
    private $allowSecondary;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Set the image resource.
     *
     * @param string|FilesModel $identifier can be a FilesModel, a tl_files uuid/id/path or a file system path
     */
    public function from($identifier): self
    {
        $this->filePath = $this->locateResource($identifier, $this->filesModel);

        $this->invalidateCache(self::CACHE_PICTURE);

        return $this;
    }

    /**
     * Set a size configuration that will be applied to the resource.
     *
     * @param int|string|array|PictureConfiguration $size a picture size configuration or reference
     */
    public function setSize($size): self
    {
        $this->sizeConfiguration = $size;

        $this->invalidateCache(self::CACHE_PICTURE);

        return $this;
    }

    /**
     * Set custom meta data. By default or if the argument is set to null, meta
     * data from the file model (if available) will be used instead.
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

        $this->invalidateCache(self::CACHE_META_DATA);

        return $this;
    }

    /**
     * Enable/disable the creation of secondary image content (e.g. a light box
     * or fullsize image). This setting is disabled by default.
     */
    public function allowSecondary(bool $allow = true): self
    {
        $this->allowSecondary = $allow;

        $this->invalidateCache(self::CACHE_TEMPLATE_DATA);

        return $this;
    }

    /**
     * Create a picture with the current size configuration.
     */
    public function getPicture(): PictureInterface
    {
        if (isset($this->cache[self::CACHE_PICTURE])) {
            return $this->cache[self::CACHE_PICTURE];
        }

        if (null === $this->filePath) {
            throw new \LogicException('You need to call `from()` to set a resource before creating an image.');
        }

        /** @var PictureFactoryInterface $pictureFactory */
        $pictureFactory = $this->locator->get('contao.image.picture_factory');

        $picture = $pictureFactory->create(
            $this->filePath, $this->sizeConfiguration
        );

        return $this->cache[self::CACHE_PICTURE] = $picture;
    }

    /**
     * Get the 'sources' part of the current picture.
     */
    public function getSources(): array
    {
        return $this->getPicture()->getSources(
            $this->getProjectDir(),
            $this->getStaticUrl()
        );
    }

    /**
     * Get the 'img' part of the current picture.
     */
    public function getImg(): array
    {
        return $this->getPicture()->getImg(
            $this->getProjectDir(),
            $this->getStaticUrl()
        );
    }

    /**
     * Get the effective meta data. If no custom meta data was set the file
     * model (if available) will be queried or an empty set returned.
     */
    public function getMetaData(): MetaData
    {
        if (null !== $this->metaData) {
            return $this->metaData;
        }

        if (isset($this->cache[self::CACHE_META_DATA])) {
            return $this->cache[self::CACHE_META_DATA];
        }

        $metaData = null;

        // Try to get meta data from files model
        if (null !== $this->filesModel) {
            // If a locale was explicitly set, use it without further fallbacks
            $locales = null !== $this->locale ? [$this->locale] : $this->getFallbackLocaleList();

            $metaData = $this->filesModel->getMetaData(...$locales);
        }

        // Fallback to a empty set
        if (null === $metaData) {
            /** @var MetaDataFactory $metaDataFactory */
            $metaDataFactory = $this->locator->get('contao.image.metadata_factory');
            $metaData = $metaDataFactory->createEmpty();
        }

        return $this->cache[self::CACHE_META_DATA] = $metaData;
    }

    /**
     * Get the image dimensions of the source file.
     */
    public function getOriginalDimensions(): ImageDimensions
    {
        if (isset($this->cache[self::CACHE_ORIGINAL_DIMENSIONS])) {
            return $this->cache[self::CACHE_ORIGINAL_DIMENSIONS];
        }

        if (null === $this->filePath) {
            throw new \LogicException('You need to call `from()` to set a resource before querying the size.');
        }

        /** @var ImageFactory $imageFactory */
        $imageFactory = $this->locator->get('contao.image.image_factory');

        return $this->cache[self::CACHE_ORIGINAL_DIMENSIONS] = $imageFactory->create($this->filePath)->getDimensions();
    }

    /**
     * Create a data set, ready to be used in templates.
     */
    public function getTemplateData(): TemplateData
    {
        if (isset($this->cache[self::CACHE_TEMPLATE_DATA])) {
            return $this->cache[self::CACHE_TEMPLATE_DATA];
        }

        // todo: Check responsibilities. We could move all of this method's
        //       logic into `TemplateData` and let it decide if it needs a
        //       secondary studio and which link mode to use. Or the other
        //       way round so that it does not need to rely on this class
        //       (would probably worsen the 'lazyness' quite a bit).

        $getLightBoxConfig = function () {
            $url = $this->getMetaData()->getUrl();
            $validImageType = $this->hasValidImageType($url);

            // A url was set but points to an unsupported image type: Open target in a new window (no lightbox)
            if (false === $validImageType) {
                return [TemplateData::LINK_NEW_WINDOW, null];
            }

            // A url was not set OR it was set and points to a supported image type: Create a lightbox
            if (null === $url || $validImageType) {
                // Do not resize external resources.
                if ($this->isExternalUrl($url)) {
                    return [TemplateData::LINK_LIGHTBOX, null];
                }

                $lightBoxStudio = $this->createDerived($url);

                if (null !== ($sizeConfiguration = $this->getLightBoxSizeConfiguration())) {
                    $lightBoxStudio->setSize($sizeConfiguration);
                }

                return [TemplateData::LINK_LIGHTBOX, $lightBoxStudio];
            }

            return [TemplateData::LINK_NONE, null];
        };

        // todo: Do we really need the scope check? We probably shouldn't do that here.
        if ($this->allowSecondary && $this->isFrontendScope()) {
            [$linkMode, $lightBoxStudio] = $getLightBoxConfig();

            $templateData = new TemplateData($this, $linkMode, $lightBoxStudio);
        } else {
            $templateData = new TemplateData($this);
        }

        return $this->cache[self::CACHE_TEMPLATE_DATA] = $templateData;
    }

    /**
     * Create a new Studio instance that is derived from the current one. If no
     * identifier is specified the derived instance will be based on the meta
     * data url or the current resource as a fallback.
     */
    public function createDerived($identifier = null): self
    {
        /** @var Studio $studio */
        $studio = $this->locator->get('contao.image.studio');

        if (null === $identifier) {
            $url = $this->getMetaData()->getUrl();
            $identifier = false === $this->isExternalUrl($url) ? $url : null;
        }

        // Explicitly set identifier, fallback to cloning values of this instance
        if (null !== $identifier) {
            $studio->from($identifier);
        } else {
            $studio->filePath = $this->filePath;
            $studio->filesModel = $this->filesModel;
        }

        return $studio;
    }

    public function getFilePath(): string
    {
        if (null === $this->filePath) {
            throw new \LogicException('You need to call `from()` to set a resource before accessing the path.');
        }

        return $this->filePath;
    }

    public static function getSubscribedServices(): array
    {
        return [
            'contao.image.studio' => self::class,
            'contao.image.picture_factory' => PictureFactoryInterface::class,
            'contao.image.image_factory' => ImageFactoryInterface::class,
            'contao.image.metadata_factory' => MetaDataFactory::class,
            'request_stack' => RequestStack::class,
            'parameter_bag' => ParameterBagInterface::class,
            'contao.assets.files_context' => ContextInterface::class,
            'contao.routing.scope_matcher' => ScopeMatcher::class,
            'contao.framework' => ContaoFramework::class,
        ];
    }

    /**
     * Recursively invalidate a cache entry and it's dependencies.
     */
    private function invalidateCache(string $key): void
    {
        // Cache dependencies: if the `key` is invalidated, all `values` will be as well
        $dependencies = [
            self::CACHE_PICTURE => [self::CACHE_META_DATA, self::CACHE_ORIGINAL_DIMENSIONS],
            self::CACHE_META_DATA => [self::CACHE_TEMPLATE_DATA],
        ];

        unset($this->cache[$key]);

        foreach ($dependencies[$key] ?? [] as $element) {
            if (isset($this->cache[$element])) {
                $this->invalidateCache($element);
            }
        }
    }

    /**
     * Returns a list of locales (if available) in the following order:
     *  1. language of current page
     *  2. root page fallback language
     *  3. request locale
     *  4. system locale.
     */
    private function getFallbackLocaleList(): array
    {
        $locales = [$this->getSystemLocale()];

        $request = $this->getRequest();

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

    private function getAdapter(string $adapter): Adapter
    {
        $framework = $this->locator->get('contao.framework');
        $framework->initialize();

        return $framework->getAdapter($adapter);
    }

    private function getProjectDir(): string
    {
        // todo: Handle PHPStan locator false positives. In this case we could also
        //       directly use constructor injection for the parameters, though.
        //       see https://github.com/phpstan/phpstan-symfony/issues/76
        return $this->locator
            ->get('parameter_bag')
            ->get('kernel.project_dir')
        ;
    }

    private function getSystemLocale(): string
    {
        return $this->locator
            ->get('parameter_bag')
            ->get('kernel.default_locale')
        ;
    }

    private function getStaticUrl(): string
    {
        return $this->locator
            ->get('contao.assets.files_context')
            ->getStaticUrl()
        ;
    }

    private function getRequest(): ?Request
    {
        return $this->locator
            ->get('request_stack')
            ->getCurrentRequest()
        ;
    }

    private function isFrontendScope(): bool
    {
        $request = $this->getRequest();

        return null !== $request && $this->locator
            ->get('contao.routing.scope_matcher')
            ->isFrontendRequest($request)
        ;
    }

    /**
     * Check if the provided file uri has a valid image file extension.
     */
    private function hasValidImageType(?string $uri): ?bool
    {
        if (null === $uri) {
            return null;
        }

        /** @var Config $configAdapter */
        $configAdapter = $this->getAdapter(Config::class);

        $validImageTypes = explode(',', $configAdapter->get('validImageTypes'));

        return \in_array(Path::getExtension($uri), $validImageTypes, true);
    }

    private function isExternalUrl(?string $uri): ?bool
    {
        if (null === $uri) {
            return null;
        }

        return preg_match('#^https?://#', $uri);
    }

    /**
     * Try to get a light box size configuration from the current page's layout
     * model. Will return null if not defined or not in a request context.
     */
    private function getLightBoxSizeConfiguration(): ?array
    {
        $request = $this->getRequest();

        if (null === $request || !$request->attributes->has('pageModel')) {
            return null;
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->getAdapter(PageModel::class);

        /** @var PageModel|null $page */
        $page = $pageAdapter->findByPk($request->attributes->get('pageModel'));

        if (null === $page || null === $page->layout) {
            return null;
        }

        /** @var LayoutModel $layoutModelAdapter */
        $layoutModelAdapter = $this->getAdapter(LayoutModel::class);

        /** @var LayoutModel|null $layoutModel */
        $layoutModel = $layoutModelAdapter->findByPk($page->layout);

        if (null === $layoutModel || empty($layoutModel->lightboxSize)) {
            return null;
        }

        return StringUtil::deserialize($layoutModel->lightboxSize, true);
    }

    /**
     * Try to locate a file by querying the DBAFS ($identifier = uuid/id/path),
     * fallback to interpret $identifier as absolute/relative file path.
     */
    private function locateResource($identifier, FilesModel &$filesModel = null): string
    {
        $dbafsItem = true;

        if ($identifier instanceof FilesModel) {
            $filesModel = $identifier;
        } else {
            // Try to interpret the identifier as a DBAFS item (and get a file model reference)

            /** @var Validator $validatorAdapter */
            $validatorAdapter = $this->getAdapter(Validator::class);

            /** @var FilesModel $filesModelAdapter */
            $filesModelAdapter = $this->getAdapter(FilesModel::class);

            if ($validatorAdapter->isUuid($identifier)) {
                $filesModel = $filesModelAdapter->findByUuid($identifier);
            } elseif (is_numeric($identifier)) {
                $filesModel = $filesModelAdapter->findById((int) $identifier);
            } else {
                $filesModel = $filesModelAdapter->findByPath($identifier);
                $dbafsItem = null !== $filesModel;
            }
        }

        if ($dbafsItem) {
            if (null === $filesModel) {
                throw new \InvalidArgumentException("DBAFS item '$identifier' could not be found . ");
            }

            if ('file' !== $filesModel->type) {
                throw new \InvalidArgumentException("DBAFS item '$identifier' is not a file . ");
            }

            return Path::makeAbsolute($filesModel->path, $this->getProjectDir());
        }

        // Interpret the identifier as a generic file system path
        if (Path::isAbsolute($identifier)) {
            return Path::canonicalize($identifier);
        }

        return Path::makeAbsolute($identifier, $this->getProjectDir());
    }
}
