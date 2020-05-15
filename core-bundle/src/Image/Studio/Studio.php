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
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Image\PictureFactoryInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\File;
use Contao\FilesModel;
use Contao\Image\Picture;
use Contao\Image\PictureConfiguration;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Template;
use Contao\Validator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Webmozart\PathUtil\Path;

final class Studio implements ServiceSubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    /**
     * @var string|null
     */
    private $filePath;

    /**
     * @var FilesModel|null
     */
    private $filesModel;

    /**
     * @var mixed|null
     */
    private $sizeConfiguration;

    /**
     * @var string|null
     */
    private $locale;

    /**
     * @var Picture|null
     */
    private $picture;

    /**
     * @var MetaData|null
     */
    private $metaData;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * Define the resource.
     *
     * @param string|FilesModel $identifier can be a FilesModel, a tl_files uuid/id/path or an absolute path
     */
    public function from($identifier): self
    {
        $this->filePath = $this->getFilePath($identifier, $this->filesModel);

        return $this;
    }

    /**
     * Apply a size configuration.
     *
     * @param int|string|array|PictureConfiguration $size a picture size configuration or reference
     */
    public function setSize($size): self
    {
        // todo: maybe move this normalization to PictureFactory or drop?
        $this->sizeConfiguration = StringUtil::deserialize($size);

        return $this;
    }

    /**
     * Set a meta data context. This will overwrite previously set meta data.
     *
     * @param ContentModel|array $context
     */
    public function setContext($context): self
    {
        $metaDataFactory = $this->locator->get('contao.image.metadata_factory');

        if ($context instanceof ContentModel) {
            $this->metaData = $metaDataFactory->createFromContentModel($context);

            return $this;
        }

        if (\is_array($context)) {
            $this->metaData = $metaDataFactory->createFromArray($context);

            return $this;
        }

        throw new \InvalidArgumentException('Unsupported context type.');
    }

    /**
     * Set meta data. This will overwrite data from a previously set context.
     */
    public function setMetaData(MetaData $metaData): self
    {
        $this->metaData = $metaData;

        return $this;
    }

    /**
     * Set a custom locale.
     */
    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Create a picture with the current configuration.
     */
    public function getPicture(): Picture
    {
        if (null === $this->filePath) {
            throw new \LogicException('You need to call `from()` to set a resource before creating an image.');
        }

        /** @var PictureFactoryInterface $pictureFactory */
        $pictureFactory = $this->locator->get('contao.image.picture_factory');

        // todo: Consider using caching for other results as well
        $this->picture = $pictureFactory->create(
            $this->filePath, $this->sizeConfiguration
        );

        return $this->picture;
    }

    /**
     * Get the 'sources' part of the current picture. Creates one if not existing yet.
     */
    public function getSources(): array
    {
        if (null === $this->picture) {
            $this->getPicture();
        }

        return $this->picture->getSources(
            $this->getProjectDir(),
            $this->getStaticUrl()
        );
    }

    /**
     * Get the 'img' part of the current picture. Creates one if not existing yet.
     */
    public function getImg(): array
    {
        if (null === $this->picture) {
            $this->getPicture();
        }

        return $this->picture->getImg(
            $this->getProjectDir(),
            $this->getStaticUrl()
        );
    }

    /**
     * Evaluate the meta data for the current resource and context.
     */
    public function getMetaData(string $locale = null): MetaData
    {
        /** @var MetaDataFactory $metaDataFactory */
        $metaDataFactory = $this->locator->get('contao.image.metadata_factory');

        if (null !== $this->filesModel) {
            $metaData = $metaDataFactory->createFromFilesModel($this->filesModel, $this->locale ?? $locale);

            return null !== $this->metaData ?
                $metaData->withOther($this->metaData) : $metaData;
        }

        if (null !== $this->metaData) {
            return $this->metaData;
        }

        return $metaDataFactory->createEmpty();
    }

    /**
     * Create a new Studio instance that is derived from the current one.
     */
    public function createDerived($identifier = null): self
    {
        /** @var Studio $studio */
        $studio = $this->locator->get('contao.image.studio');

        $metaData = $this->getMetaData();
        $identifier = $identifier ?? $metaData->getUrl();

        // Explicitly set identifier, fallback to cloning values of this instance
        if (null !== $identifier) {
            $studio->from($identifier);
        } else {
            $studio->filePath = $this->filePath;
            $studio->filesModel = $this->filesModel;
        }

        return $studio;
    }

    /**
     * Build an array of template data.
     */
    public function getTemplateData(string $locale = null, string $lightBoxId = null): array
    {
        $metaData = $this->getMetaData($locale);

        /** @var Controller $controllerAdapter */
        $controllerAdapter = $this->getAdapter(Controller::class);

        // Primary image
        [$originalWidth, $originalHeight] = $this->getOriginalSize();
        $floating = $metaData->getFloatingProperty();

        $templateData = array_merge(
            $metaData->getAllValues(),
            [
                'picture' => [
                    'img' => $this->getImg(),
                    'sources' => $this->getSources(),
                    'alt' => $metaData->getAlt(),
                ],
                'width' => $originalWidth,
                'height' => $originalHeight,
                'arrSize' => [$originalWidth, $originalHeight],
                'imgSize' => sprintf(' width="%d" height="%d"', $originalWidth, $originalHeight),
                'singleSRC' => $this->filePath,
                'fullsize' => $metaData->shouldDisplayFullSize() ?? false,
                'margin' => $controllerAdapter->generateMargin($metaData->getMarginProperty()),
                'addBefore' => 'below' !== $floating,
                'addImage' => true,
            ]
        );

        if (null !== ($title = $metaData->getTitle())) {
            $templateData['picture']['title'] = $title;
        }

        if (null !== $floating) {
            $templateData['floatClass'] = ' float_'.$floating;
        }

        if (!$metaData->shouldDisplayFullSize() || !$this->isFrontendScope()) {
            return $templateData;
        }

        // Fullsize/LightBox image and links
        $url = $metaData->getUrl();
        $validImageType = $this->hasValidImageType($url);

        if (false === $validImageType) {
            return array_merge(
                $templateData,
                [
                    'href' => $url,
                    'attributes' => ' target="_blank"',
                ]
            );
        }

        if (null === $url || $validImageType) {
            if (null === $lightBoxId) {
                // todo: this must be unique but can be arbitrary, right?
                $lightBoxId = substr(md5($url ?? $this->filePath), 0, 6);
            }

            $templateData['attributes'] = sprintf(' data-lightbox="%s"', $lightBoxId);

            if ($this->isExternalUrl($url)) {
                return $templateData;
            }

            $studio = $this->createDerived($url);

            if (null !== ($sizeConfiguration = $this->getLightboxSizeConfiguration())) {
                $studio->setSize($sizeConfiguration);
            }

            if (!empty($templateData['imageTitle']) && empty($templateData['linkTitle'])) {
                $templateData['linkTitle'] = $templateData['imageTitle'];
                unset($templateData['imageTitle']);
            }

            $img = $studio->getImg();

            return array_merge(
                $templateData,
                [
                    'lightboxPicture' => [
                        'img' => $img,
                        'sources' => $studio->getSources(),
                    ],
                    'href' => $img['src'],
                ]
            );
        }

        return $templateData;
    }

    /**
     * Generate and apply template data to an existing template.
     */
    public function applyToTemplate(Template $template): void
    {
        $studioData = $this->getTemplateData();
        $templateData = $template->getData();

        // Do not override the "href" key (see #6468)
        if (isset($studioData['href'], $templateData['href'])) {
            $studioData['imageHref'] = $studioData['href'];
            unset($studioData['href']);
        }

        // Append attributes instead of replacing
        if (isset($studioData['attributes'], $templateData['attributes'])) {
            $studioData['attributes'] = ($templateData['attributes'] ?? '').$studioData['attributes'];
        }

        $template->setData(array_replace_recursive($templateData, $studioData));
    }

    public static function getSubscribedServices(): array
    {
        return [
            'contao.image.studio' => self::class,
            'contao.image.picture_factory' => PictureFactoryInterface::class,
            'contao.image.metadata_factory' => MetaDataFactory::class,
            'request_stack' => RequestStack::class,
            'parameter_bag' => ParameterBagInterface::class,
            'contao.assets.files_context' => ContextInterface::class,
            'contao.routing.scope_matcher' => ScopeMatcher::class,
            'contao.framework' => ContaoFramework::class,
        ];
    }

    private function getAdapter(string $adapter): Adapter
    {
        $framework = $this->locator->get('contao.framework');
        $framework->initialize();

        return $framework->getAdapter($adapter);
    }

    private function getProjectDir(): string
    {
        return $this->locator
            ->get('parameter_bag')
            ->get('kernel.project_dir')
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

    private function getOriginalSize(): array
    {
        if (null === $this->filePath) {
            throw new \LogicException('You need to call `from()` to set a resource before querying the size.');
        }

        $relativePath = Path::makeRelative($this->filePath, $this->getProjectDir());

        // todo: Can we do this any better?
        return (new File($relativePath))->imageSize;
    }

    private function getLightboxSizeConfiguration(): ?array
    {
        $request = $this->getRequest();

        if (null === $request || !$request->attributes->has('pageModel')) {
            return null;
        }

        /** @var PageModel $pageAdapter */
        $pageAdapter = $this->getAdapter(PageModel::class);

        /** @var PageModel $page */
        $page = $pageAdapter->findByPk($request->attributes->get('pageModel'));

        if (null === $page || null === $page->layout) {
            return null;
        }

        /** @var LayoutModel $layoutModelAdapter */
        $layoutModelAdapter = $this->getAdapter(LayoutModel::class);

        /** @var LayoutModel $layoutModel */
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
    private function getFilePath($identifier, FilesModel &$filesModel = null): string
    {
        $dbafsItem = true;

        if ($identifier instanceof FilesModel) {
            $filesModel = $identifier;
        } else {
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

        if (Path::isAbsolute($identifier)) {
            return Path::canonicalize($identifier);
        }

        return Path::makeAbsolute($identifier, $this->getProjectDir());
    }
}
