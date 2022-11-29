<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\Model\Collection;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

abstract class AbstractPageRouteProvider implements RouteProviderInterface
{
    public function __construct(protected ContaoFramework $framework, protected CandidatesInterface $candidates, protected PageRegistry $pageRegistry)
    {
    }

    /**
     * @return array<PageModel>
     */
    protected function findCandidatePages(Request $request): array
    {
        $candidates = array_map('strval', $this->candidates->getCandidates($request));

        if (empty($candidates)) {
            return [];
        }

        $ids = [];
        $aliases = [];

        foreach ($candidates as $candidate) {
            if (preg_match('/^[1-9]\d*$/', $candidate)) {
                $ids[] = (int) $candidate;
            } else {
                $aliases[] = $candidate;
            }
        }

        $conditions = [];

        if (!empty($ids)) {
            $conditions[] = 'tl_page.id IN ('.implode(',', $ids).')';
        }

        if (!empty($aliases)) {
            $conditions[] = 'tl_page.alias IN ('.implode(',', array_fill(0, \count($aliases), '?')).')';
        }

        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findBy([implode(' OR ', $conditions)], $aliases);

        if (!$pages instanceof Collection) {
            return [];
        }

        /** @var array<PageModel> $models */
        $models = $pages->getModels();

        return array_filter($models, fn (PageModel $model) => $this->pageRegistry->isRoutable($model));
    }

    /**
     * @return array<int>
     */
    protected function getPageIdsFromNames(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            if (!str_starts_with($name, 'tl_page.')) {
                continue;
            }

            [, $id] = explode('.', (string) $name);

            if (!preg_match('/^[1-9]\d*$/', $id)) {
                continue;
            }

            $ids[] = (int) $id;
        }

        return array_unique($ids);
    }

    protected function compareRoutes(Route $a, Route $b, array $languages = null): int
    {
        if ('' !== $a->getHost() && '' === $b->getHost()) {
            return -1;
        }

        if ('' === $a->getHost() && '' !== $b->getHost()) {
            return 1;
        }

        /** @var PageModel|null $pageA */
        $pageA = $a->getDefault('pageModel');

        /** @var PageModel|null $pageB */
        $pageB = $b->getDefault('pageModel');

        // Check if the page models are valid (should always be the case, as routes are generated from pages)
        if (!$pageA instanceof PageModel || !$pageB instanceof PageModel) {
            return 0;
        }

        $langA = null;
        $langB = null;

        if (null !== $languages && $pageA->rootLanguage !== $pageB->rootLanguage) {
            $fallbackA = LocaleUtil::getFallbacks($pageA->rootLanguage);
            $fallbackB = LocaleUtil::getFallbacks($pageB->rootLanguage);
            $langA = $this->getLocalePriority($fallbackA, $fallbackB, $languages);
            $langB = $this->getLocalePriority($fallbackB, $fallbackA, $languages);

            if (null === $langA && null === $langB && LocaleUtil::getPrimaryLanguage($pageA->rootLanguage) === LocaleUtil::getPrimaryLanguage($pageB->rootLanguage)) {
                // If both pages have the same language without region and neither region has a priority,
                // (e.g. user prefers "de" but we have "de-CH" and "de-DE"), sort by their root page order.
                $langA = $pageA->rootSorting;
                $langB = $pageB->rootSorting;
            }
        }

        if (null === $langA && null === $langB) {
            if ($pageA->rootIsFallback && !$pageB->rootIsFallback) {
                return -1;
            }

            if ($pageB->rootIsFallback && !$pageA->rootIsFallback) {
                return 1;
            }
        } else {
            if (null === $langA && null !== $langB) {
                return 1;
            }

            if (null !== $langA && null === $langB) {
                return -1;
            }

            if ($langA < $langB) {
                return -1;
            }

            if ($langA > $langB) {
                return 1;
            }
        }

        if ('root' !== $pageA->type && 'root' === $pageB->type) {
            return -1;
        }

        if ('root' === $pageA->type && 'root' !== $pageB->type) {
            return 1;
        }

        if ($pageA->routePriority !== $pageB->routePriority) {
            return $pageB->routePriority <=> $pageA->routePriority;
        }

        $pathA = $a instanceof PageRoute && $a->getUrlSuffix() ? substr($a->getPath(), 0, -\strlen($a->getUrlSuffix())) : $a->getPath();
        $pathB = $b instanceof PageRoute && $b->getUrlSuffix() ? substr($b->getPath(), 0, -\strlen($b->getUrlSuffix())) : $b->getPath();

        // Prioritize the default behaviour when "requireItem" is enabled
        if ($pathA === $pathB && str_ends_with($pathA, '{!parameters}')) {
            $paramA = $a->getRequirement('parameters');
            $paramB = $b->getRequirement('parameters');

            if ('/.+?' === $paramA && '(/.+?)?' === $paramB) {
                return -1;
            }

            if ('(/.+?)?' === $paramA && '/.+?' === $paramB) {
                return 1;
            }
        }

        $countA = \count(explode('/', $pathA));
        $countB = \count(explode('/', $pathB));

        if ($countA > $countB) {
            return -1;
        }

        if ($countB > $countA) {
            return 1;
        }

        return strnatcasecmp($pathA, $pathB);
    }

    protected function convertLanguagesForSorting(array $languages): array
    {
        $result = [];

        foreach ($languages as $language) {
            if (!$locales = LocaleUtil::getFallbacks($language)) {
                continue;
            }

            $language = array_pop($locales);
            $result[] = $language;

            foreach (array_reverse($locales) as $locale) {
                if (!\in_array($locale, $result, true)) {
                    $result[] = $locale;
                }
            }
        }

        return array_flip($result);
    }

    private function getLocalePriority(array $locales, array $notIn, array $languagePriority): int|null
    {
        foreach (array_reverse($locales) as $locale) {
            if (isset($languagePriority[$locale]) && !\in_array($locale, $notIn, true)) {
                return $languagePriority[$locale];
            }
        }

        return null;
    }
}
