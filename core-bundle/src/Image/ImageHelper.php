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

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\Image\PictureConfiguration;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\Validator;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\PathUtil\Path;

class ImageHelper
{
    public const PICTURE_IMAGE = 'img';
    public const PICTURE_SOURCES = 'sources';
    public const META_DATA = 'metadata';
    public const TEMPLATE_DATA = 'template_data';

    /**
     * @var PictureFactoryInterface
     */
    private $pictureFactory;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $staticUrl;

    /**
     * @var MetaDataFactory
     */
    private $metaDataFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(PictureFactoryInterface $pictureFactory, string $rootDir, ContaoContext $fileContext, MetaDataFactory $metaDataFactory, RequestStack $requestStack, ContaoFramework $framework)
    {
        $this->pictureFactory = $pictureFactory;
        $this->rootDir = $rootDir;
        $this->staticUrl = $fileContext->getStaticUrl();
        $this->metaDataFactory = $metaDataFactory;
        $this->requestStack = $requestStack;
        $this->framework = $framework;
    }

    /**
     * Create a resized picture and get available metadata. The template data
     * key contains data ready to use with the default image template.
     *
     * @param string|FilesModel                          $identifier        can be a tl_files uuid/id/path or an absolute path
     * @param int|string|array|PictureConfiguration|null $sizeConfiguration a picture size configuration or reference
     * @param string|null                                $locale            locale - set to null to use default
     *
     * @return array<string, mixed> the processed picture and template data
     */
    public function createPicture($identifier, $sizeConfiguration, string $locale = null, MetaData $metaDataOverwrite = null): array
    {
        $filePath = $this->getFilepath($identifier, $filesModel);
        $picture = $this->pictureFactory->create($filePath, $this->normalizeSizeConfiguration($sizeConfiguration));

        $image = $picture->getImg($this->rootDir, $this->staticUrl);
        $sources = $picture->getSources($this->rootDir, $this->staticUrl);

        $metaData = $this->getMetaData($filesModel, $locale, $metaDataOverwrite);

        $templateData = array_merge(
            $metaData->getAll(),
            [
                'picture' => [
                    'img' => $image,
                    'sources' => $sources,
                    'alt' => $metaData->getAlt(),
                ],
            ]
        );

        return [
            self::PICTURE_IMAGE => $image,
            self::PICTURE_SOURCES => $sources,
            self::META_DATA => $metaData,
            self::TEMPLATE_DATA => $templateData,
        ];
    }

    /**
     * // todo.
     */
    public function createLightboxPicture($identifier, $sizeConfiguration = null, string $lightBoxId = null): array
    {
        if (null === $sizeConfiguration) {
            $sizeConfiguration = $this->getLightboxSizeConfiguration();
        } else {
            $sizeConfiguration = $this->normalizeSizeConfiguration($sizeConfiguration);
        }

        $filePath = $this->getFilepath($identifier);
        $picture = $this->pictureFactory->create($filePath, $sizeConfiguration);

        $image = $picture->getImg($this->rootDir, $this->staticUrl);
        $sources = $picture->getSources($this->rootDir, $this->staticUrl);

        if (null === $lightBoxId) {
            $lightBoxId = substr(md5($filePath), 0, 6); // fixme
        }

        // todo: all that lightbox magic, maybe refactor things to metadata

        return [
            self::PICTURE_IMAGE => $image,
            self::PICTURE_SOURCES => $sources,
            self::TEMPLATE_DATA => [
                'lightboxPicture' => [
                    'img' => $image,
                    'sources' => $sources,
                ],
                'src' => $image['src'],
                'attributes' => sprintf(' data-lightbox="%s"', $lightBoxId),
            ],
        ];
    }

    private function normalizeSizeConfiguration($sizeConfiguration)
    {
        // todo: maybe move to PictureFactory or drop?
        return StringUtil::deserialize($sizeConfiguration);
    }

    /**
     * Try to locate a file by querying the DBAFS ($identifier = uuid/id/path),
     * fallback to interpret $identifier as absolute/relative file path.
     */
    private function getFilepath($identifier, FilesModel &$filesModel = null): string
    {
        $dbafsItem = true;

        if ($identifier instanceof FilesModel) {
            $filesModel = $identifier;
        } else {
            $this->framework->initialize();

            /** @var Validator $validatorAdapter */
            $validatorAdapter = $this->framework->getAdapter(Validator::class);

            /** @var FilesModel $filesModelAdapter */
            $filesModelAdapter = $this->framework->getAdapter(FilesModel::class);

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
                throw new \InvalidArgumentException("DBAFS item '$identifier' could not be found.");
            }

            if ('file' !== $filesModel->type) {
                throw new \InvalidArgumentException("DBAFS item '$identifier' is not a file.");
            }

            return Path::makeAbsolute($filesModel->path, $this->rootDir);
        }

        if (Path::isAbsolute($identifier)) {
            return Path::canonicalize($identifier);
        }

        return Path::makeAbsolute($identifier, $this->rootDir);
    }

    /**
     * Retrieve meta data from files model (if available), allow overwriting and
     * fallback to an empty container if no data is present.
     */
    private function getMetaData($filesModel, ?string $locale, ?MetaData $metaDataOverwrite): MetaData
    {
        if (null === $filesModel) {
            return $metaDataOverwrite ?? $this->metaDataFactory->createEmpty();
        }

        $metaData = $this->metaDataFactory->createFromFilesModel($filesModel, $locale);

        if (null !== $metaDataOverwrite) {
            $metaData = $metaData->withOverwrites($metaData);
        }

        return $metaData;
    }

    /**
     * Try to get a lightbox configuration for the current page.
     */
    private function getLightboxSizeConfiguration(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && $request->attributes->has('pageModel')) {
            $this->framework->initialize();

            /** @var PageModel $page */
            $page = $request->attributes->get('pageModel');

            /** @var LayoutModel $layoutModelAdapter */
            $layoutModelAdapter = $this->framework->getAdapter(LayoutModel::class);

            /** @var LayoutModel $layoutModel */
            $layoutModel = $layoutModelAdapter->findByPk($page->layoutId);

            if (null !== $layoutModel) {
                return StringUtil::deserialize($layoutModel->lightboxSize, true);
            }
        }

        throw new \RuntimeException('Could not retrieve layout model. Please provide a size configuration.');
    }
}
