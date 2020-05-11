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

use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\Model;
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

        $fileModelDefaults = array_keys($GLOBALS['TL_DCA']['tl_files']['fields']['meta']['eval']['metaFields'] ?? []);
        $imageTemplateDefaults = ['floatClass', 'margin', 'href', 'linkTitle', 'attributes', 'caption', 'alt'];

        return array_unique(array_merge($fileModelDefaults, $imageTemplateDefaults));
    }

    /**
     * Create meta data from a files model.
     */
    public function createFromFilesModel(FilesModel $filesModel, string $customLocale = null): MetaData
    {
        $metaDataRaw = StringUtil::deserialize($filesModel->meta, true);
        $locales = null !== $customLocale ? [$customLocale] : $this->getLocales();
        $metaData = [];

        // get data from first matching locale
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
    public function createFromModel(Model $model): MetaData
    {
        return new MetaData($this->normalize($model->row()), $this->getMetaFields());
    }

    /**
     * Create meta data from an array.
     */
    public function createFromArray(array $values): MetaData
    {
        return new MetaData($this->normalize($values), $this->getMetaFields());
    }

    /**
     * Create an empty meta data container.
     */
    public function createEmpty(): MetaData
    {
        return new MetaData([], $this->getMetaFields());
    }

    private function normalize(array $values): array
    {
        // fixme: correctly setup mapping

        $mapping = [
            'imageTitle' => 'title',
            'imageUrl' => 'link',
        ];

        foreach (array_intersect_key($mapping, $values) as $from => $to) {
            $values[$to] = $values[$from];
            unset($values[$from]);
        }

        return $values;
    }
}
