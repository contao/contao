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

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\PageModel;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\RequestStack;

class MetaDataFactory
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $fallbackLocale;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(RequestStack $requestStack, string $fallbackLocale, ContaoFramework $framework)
    {
        $this->requestStack = $requestStack;
        $this->fallbackLocale = $fallbackLocale;
        $this->framework = $framework;
    }

    /**
     * Returns an array of locales ordered by priority:
     *  1. language of current page
     *  2. root page fallback language
     *  3. request locale
     *  4. system locale.
     */
    public function getLocales(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return [$this->fallbackLocale];
        }

        $locales = [$request->getLocale(), $this->fallbackLocale];

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

    /**
     * Returns the meta fields registered in `tl_files.meta`.
     */
    public function getMetaFields(): array
    {
        $this->framework->initialize();

        /** @var Controller $controller */
        $controller = $this->framework->getAdapter(Controller::class);
        $controller->loadDataContainer('tl_files');

        return array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] ?? []);
    }

    /**
     * Create meta data from a files model.
     */
    public function createFromFilesModel(FilesModel $filesModel, string $customLocale = null): MetaData
    {
        $metaDataRaw = StringUtil::deserialize($filesModel->meta, true);
        $locales = null !== $customLocale ? [$customLocale] : $this->getLocales();
        $metaData = [];

        foreach ($locales as $locale) {
            $metaData = $metaDataRaw[$locale] ?? [];

            if (!empty($metaData)) {
                break;
            }
        }

        return new MetaData($metaData, $this->getMetaFields());
    }

    /**
     * Create meta data from a content or module model.
     */
    public function createFromContentModel(ContentModel $model): MetaData
    {
        $values = MetaData::remap($model->row(), [
            'imageTitle' => MetaData::VALUE_TITLE,
            'imageUrl' => MetaData::VALUE_URL,
        ]);

        // todo: handle `metaIgnore`?
        $properties = [
            MetaData::PROPERTY_DOMINANT_VALUES => '1' === $model->overwriteMeta,
            MetaData::PROPERTY_FULLSIZE => '1' === $model->fullsize,
            MetaData::PROPERTY_MARGIN => StringUtil::deserialize($model->imagemargin, true),
            MetaData::PROPERTY_FLOATING => $model->floating,
        ];

        return new MetaData($this->canonicalizeValues($values, $this->getMetaFields()), $properties);
    }

    /**
     * Create meta data from an array.
     */
    public function createFromArray(array $values, bool $canonicalize = true): MetaData
    {
        if ($canonicalize) {
            $values = $this->canonicalizeValues($values, $this->getMetaFields());
        }

        return new MetaData($values);
    }

    /**
     * Create an empty meta data container.
     */
    public function createEmpty(): MetaData
    {
        return $this->createFromArray([]);
    }

    private function canonicalizeValues(array $values, array $allowedFields): array
    {
        // Strip superfluous
        $values = array_intersect_key($values, array_flip($allowedFields));

        // Fill possibly missing fields with empty values
        return array_merge(
            array_combine($allowedFields, array_fill(0, \count($allowedFields), '')),
            $values
        );
    }
}
