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
    public function __construct(
        protected ContaoFramework $framework,
        protected CandidatesInterface $candidates,
        protected PageRegistry $pageRegistry,
    ) {
    }

    /**
     * @return array<PageModel>
     */
    protected function findCandidatePages(Request $request): array
    {
        $candidates = array_map(\strval(...), $this->candidates->getCandidates($request));

        if (!$candidates) {
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

        if ($ids) {
            $conditions[] = 'tl_page.id IN ('.implode(',', $ids).')';
        }

        if ($aliases) {
            $conditions[] = 'tl_page.alias IN ('.implode(',', array_fill(0, \count($aliases), '?')).')';
        }

        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findBy([implode(' OR ', $conditions)], $aliases);

        if (!$pages instanceof Collection) {
            return [];
        }

        $models = $pages->getModels();

        return array_filter($models, $this->pageRegistry->isRoutable(...));
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

    protected function compareRoutes(Route $a, Route $b, array|null $languages = null): int
    {
        // Group routes by host so language priorities on other domains cannot affect
        // their order. Sort empty hosts last as they are fallbacks.
        if ($a->getHost() !== $b->getHost()) {
            return strcmp($b->getHost(), $a->getHost()) <=> 0;
        }

        /** @var PageModel|null $pageA */
        $pageA = $a->getDefault('pageModel');

        /** @var PageModel|null $pageB */
        $pageB = $b->getDefault('pageModel');

        // Routes generated from pages should always have a page model. Keep any other
        // routes in a separate group so they cannot compare equal to all pages.
        if (!$pageA instanceof PageModel || !$pageB instanceof PageModel) {
            return ($pageB instanceof PageModel) <=> ($pageA instanceof PageModel);
        }

        $langA = null !== $languages ? $this->getLocalePriority(LocaleUtil::getFallbacks($pageA->rootLanguage), $languages) : null;
        $langB = null !== $languages ? $this->getLocalePriority(LocaleUtil::getFallbacks($pageB->rootLanguage), $languages) : null;

        if (null === $langA && null !== $langB) {
            return 1;
        }

        if (null !== $langA && null === $langB) {
            return -1;
        }

        if ($langA !== $langB) {
            return $langA <=> $langB;
        }

        if (null !== $langA) {
            // Equally preferred languages use root page order, including pages with
            // identical locales. Unmatched languages use the fallback flag instead.
            if ($order = $pageA->rootSorting <=> $pageB->rootSorting) {
                return $order;
            }
        } elseif ($order = (bool) $pageB->rootIsFallback <=> (bool) $pageA->rootIsFallback) {
            return $order;
        }

        if ('root' !== $pageA->type && 'root' === $pageB->type) {
            return -1;
        }

        if ('root' === $pageA->type && 'root' !== $pageB->type) {
            return 1;
        }

        if ($order = $pageB->routePriority <=> $pageA->routePriority) {
            return $order;
        }

        $pathA = $a instanceof PageRoute && $a->getUrlSuffix() ? substr($a->getPath(), 0, -\strlen($a->getUrlSuffix())) : $a->getPath();
        $pathB = $b instanceof PageRoute && $b->getUrlSuffix() ? substr($b->getPath(), 0, -\strlen($b->getUrlSuffix())) : $b->getPath();

        $countA = \count(explode('/', $pathA));
        $countB = \count(explode('/', $pathB));

        if ($countA > $countB) {
            return -1;
        }

        if ($countB > $countA) {
            return 1;
        }

        if ($order = strnatcasecmp($pathA, $pathB)) {
            return $order;
        }

        // Use the same path equivalence as above and rank each requirement
        // independently, including custom requirements.
        $requiredA = str_ends_with($pathA, '{!parameters}') && '/.+?' === $a->getRequirement('parameters');
        $requiredB = str_ends_with($pathB, '{!parameters}') && '/.+?' === $b->getRequirement('parameters');

        return $requiredB <=> $requiredA;
    }

    protected function convertLanguagesForSorting(array $languages): array
    {
        $result = [];

        // Shared fallbacks follow the last requested variant, even if the generic
        // language was requested earlier. This gives every locale a fixed priority.
        foreach ($languages as $language) {
            foreach (array_reverse(LocaleUtil::getFallbacks($language)) as $locale) {
                unset($result[$locale]);
                $result[$locale] = true;
            }
        }

        return array_flip(array_keys($result));
    }

    private function getLocalePriority(array $locales, array $languagePriority): int|null
    {
        foreach (array_reverse($locales) as $locale) {
            if (isset($languagePriority[$locale])) {
                return $languagePriority[$locale];
            }
        }

        return null;
    }
}
