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
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\Model\Collection;
use Contao\PageModel;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

abstract class AbstractPageRouteProvider implements RouteProviderInterface
{
    /**
     * @var ContaoFramework
     */
    protected $framework;

    /**
     * @var CandidatesInterface
     */
    protected $candidates;

    public function __construct(ContaoFramework $framework, CandidatesInterface $candidates)
    {
        $this->framework = $framework;
        $this->candidates = $candidates;
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

        /** @var PageModel $pageModel */
        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findBy([implode(' OR ', $conditions)], $aliases);

        if (!$pages instanceof Collection) {
            return [];
        }

        /** @var array<PageModel> */
        return $pages->getModels();
    }

    /**
     * @return array<int>
     */
    protected function getPageIdsFromNames(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            if (0 !== strncmp($name, 'tl_page.', 8)) {
                continue;
            }

            [, $id] = explode('.', $name);

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
            $langA = $languages[$pageA->rootLanguage] ?? null;
            $langB = $languages[$pageB->rootLanguage] ?? null;

            $primaryA = substr($pageA->rootLanguage, 0, 2);
            $primaryB = substr($pageB->rootLanguage, 0, 2);

            if ($primaryA !== $primaryB) {
                // We must not compare by language (without region) if they are the same, otherwise
                // two pages with same language but different regions might not be sorted by region priority.
                if (null === $langA) {
                    $langA = $languages[$primaryA] ?? null;
                }

                if (null === $langB) {
                    $langB = $languages[$primaryB] ?? null;
                }
            } elseif (null === $langA && null === $langB) {
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

        return strnatcasecmp($pathA, $pathB);
    }

    protected function convertLanguagesForSorting(array $languages): array
    {
        $result = [];

        foreach ($languages as $language) {
            $language = str_replace('_', '-', $language);
            $result[] = $language;

            if (5 === \strlen($language)) {
                $lng = substr($language, 0, 2);

                // For [de-DE, fr, de] this will add "de" as second item in this loop,
                // but the only last item will be kept by array_flip.
                if (!\in_array($lng, $result, true)) {
                    $result[] = $lng;
                }
            }
        }

        return array_flip($result);
    }
}
