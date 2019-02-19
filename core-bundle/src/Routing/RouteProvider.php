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

use Contao\Config;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteProvider implements RouteProviderInterface
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $database;

    /**
     * @var string
     */
    private $urlSuffix;

    /**
     * @var bool
     */
    private $prependLocale;

    public function __construct(ContaoFramework $framework, Connection $database, string $urlSuffix, bool $prependLocale)
    {
        $this->framework = $framework;
        $this->database = $database;
        $this->urlSuffix = $urlSuffix;
        $this->prependLocale = $prependLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $this->framework->initialize(true);

        $pathInfo = rawurldecode($request->getPathInfo());

        // The request string must not contain "auto_item" (see #4012)
        if (false !== strpos($pathInfo, '/auto_item/')) {
            return new RouteCollection();
        }

        $routes = [];

        if ('/' === $pathInfo || ($this->prependLocale && preg_match('@^/([a-z]{2}(-[A-Z]{2})?)/$@', $pathInfo))) {
            $this->addRoutesForRootPages($this->findRootPages($request->getHttpHost()), $routes);

            return $this->createCollectionForRoutes($routes, $request->getLanguages());
        }

        $pathInfo = $this->removeSuffixAndLanguage($pathInfo);

        if (null === $pathInfo) {
            return new RouteCollection();
        }

        $candidates = $this->getAliasCandidates($pathInfo);
        $pages = $this->findPages($candidates);

        $this->addRoutesForPages($pages, $routes);

        return $this->createCollectionForRoutes($routes, $request->getLanguages());
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name): Route
    {
        $this->framework->initialize(true);

        $ids = $this->getPageIdsFromNames([$name]);

        if (empty($ids)) {
            throw new RouteNotFoundException('Route name does not match a page ID');
        }

        /** @var PageModel $pageModel */
        $pageModel = $this->framework->getAdapter(PageModel::class);
        $page = $pageModel->findByPk($ids[0]);

        if (null === $page) {
            throw new RouteNotFoundException(sprintf('Page ID "%s" not found', $ids[0]));
        }

        $routes = [];

        $this->addRoutesForPage($page, $routes);

        return $routes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names): array
    {
        $this->framework->initialize(true);

        /** @var PageModel $pageModel */
        $pageModel = $this->framework->getAdapter(PageModel::class);

        if (null === $names) {
            $pages = $pageModel->findAll();
        } else {
            $ids = $this->getPageIdsFromNames($names);

            if (empty($ids)) {
                return [];
            }

            $pages = $pageModel->findBy('tl_page.id IN ('.implode(',', $ids).')', []);
        }

        if (!$pages instanceof Collection) {
            return [];
        }

        $routes = [];

        $this->addRoutesForPages($pages, $routes);
        $this->sortRoutes($routes);

        return $routes;
    }

    private function removeSuffixAndLanguage(string $pathInfo): ?string
    {
        $suffixLength = \strlen($this->urlSuffix);

        if (0 !== $suffixLength) {
            if (substr($pathInfo, -$suffixLength) !== $this->urlSuffix) {
                return null;
            }

            $pathInfo = substr($pathInfo, 0, -$suffixLength);
        }

        if (0 === strncmp($pathInfo, '/', 1)) {
            $pathInfo = substr($pathInfo, 1);
        }

        if ($this->prependLocale) {
            $matches = [];

            if (!preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.+)$@', $pathInfo, $matches)) {
                return null;
            }

            $pathInfo = $matches[3];
        }

        return $pathInfo;
    }

    /**
     * Compiles all possible aliases by applying dirname() to the request (e.g. news/archive/item, news/archive, news).
     *
     * @return string[]
     */
    private function getAliasCandidates(string $pathInfo): array
    {
        $pos = strpos($pathInfo, '/');

        if (false === $pos) {
            return [$pathInfo];
        }

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        if (!$config->get('folderUrl')) {
            return [substr($pathInfo, 0, $pos)];
        }

        $candidates = [$pathInfo];

        while ('/' !== $pathInfo && false !== strpos($pathInfo, '/')) {
            $pathInfo = \dirname($pathInfo);
            $candidates[] = $pathInfo;
        }

        return $candidates;
    }

    /**
     * @param PageModel[] $pages
     */
    private function addRoutesForPages(iterable $pages, array &$routes): void
    {
        foreach ($pages as $page) {
            $this->addRoutesForPage($page, $routes);
        }
    }

    /**
     * @param PageModel[] $pages
     */
    private function addRoutesForRootPages(array $pages, array &$routes): void
    {
        if (null === $pages) {
            return;
        }

        foreach ($pages as $page) {
            $this->addRoutesForRootPage($page, $routes);
        }
    }

    private function createCollectionForRoutes(array $routes, array $languages): RouteCollection
    {
        $this->sortRoutes($routes, $languages);

        $collection = new RouteCollection();

        foreach ($routes as $name => $route) {
            $collection->add($name, $route);
        }

        return $collection;
    }

    private function addRoutesForPage(PageModel $page, array &$routes): void
    {
        $page->loadDetails();

        $defaults = $this->getRouteDefaults($page);
        $defaults['parameters'] = '';

        $requirements = ['parameters' => '(/.+)?'];
        $path = sprintf('/%s{parameters}%s', $page->alias ?: $page->id, $this->urlSuffix);

        if ($this->prependLocale) {
            $path = '/{_locale}'.$path;
            $requirements['_locale'] = $page->rootLanguage;
        }

        $routes['tl_page.'.$page->id] = new Route(
            $path,
            $defaults,
            $requirements,
            ['utf8' => true],
            $page->domain,
            $page->rootUseSSL ? 'https' : null
        );

        $this->addRoutesForRootPage($page, $routes);
    }

    private function addRoutesForRootPage(PageModel $page, array &$routes): void
    {
        if ('root' !== $page->type && 'index' !== $page->alias && '/' !== $page->alias) {
            return;
        }

        $page->loadDetails();

        $path = '/';
        $requirements = [];
        $condition = null;
        $defaults = $this->getRouteDefaults($page);

        if ($this->prependLocale) {
            $path = '/{_locale}'.$path;
            $requirements['_locale'] = $page->rootLanguage;
        }

        if (!$page->rootIsFallback) {
            $condition = "'{$page->rootLanguage}' in request.getLanguages()";
        }

        $routes['tl_page.'.$page->id.'.root'] = new Route(
            $path,
            $defaults,
            $requirements,
            [],
            $page->domain,
            $page->rootUseSSL ? 'https' : null,
            [],
            $this->prependLocale ? null : $condition
        );

        /** @var Config $config */
        $config = $this->framework->getAdapter(Config::class);

        if (!$config->get('doNotRedirectEmpty')) {
            $defaults['_controller'] = 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction';
            $defaults['path'] = '/'.$page->language.'/';
            $defaults['permanent'] = true;
        }

        $routes['tl_page.'.$page->id.'.fallback'] = new Route(
            '/',
            $defaults,
            [],
            [],
            $page->domain,
            $page->rootUseSSL ? 'https' : null,
            [],
            $condition
        );
    }

    /**
     * @return array<string,PageModel|bool|string>
     */
    private function getRouteDefaults(PageModel $page): array
    {
        return [
            '_token_check' => true,
            '_controller' => 'Contao\FrontendIndex::renderPage',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
            '_locale' => $page->rootLanguage,
            'pageModel' => $page,
        ];
    }

    /**
     * @return int[]
     */
    private function getPageIdsFromNames(array $names): array
    {
        $ids = [];

        foreach ($names as $name) {
            if (0 !== strncmp($name, 'tl_page.', 8)) {
                continue;
            }

            [, $id] = explode('.', $name);

            if (!is_numeric($id)) {
                continue;
            }

            $ids[] = (int) $id;
        }

        return array_unique($ids);
    }

    /**
     * Sorts routes so that the FinalMatcher will correctly resolve them.
     *
     * 1. The ones with hostname should come first, so the ones with empty host are only taken if no hostname matches
     * 2. Root pages come last, so non-root pages with index alias (= identical path) match first
     * 3. Root/Index pages must be sorted by accept language and fallback, so the best language matches first
     * 4. Pages with longer alias (folder page) must come first to match if applicable
     */
    private function sortRoutes(array &$routes, array $languages = null): void
    {
        // Convert languages array so key is language and value is priority
        if (null !== $languages) {
            $languages = array_flip(array_values($languages));
        }

        uasort($routes, function (Route $a, Route $b) use ($languages, $routes) {
            $fallbackA = '.fallback' === substr(array_search($a, $routes, true), -9);
            $fallbackB = '.fallback' === substr(array_search($b, $routes, true), -9);

            if ($fallbackA && !$fallbackB) {
                return 1;
            }

            if ($fallbackB && !$fallbackA) {
                return -1;
            }

            if ('' !== $a->getHost() && '' === $b->getHost()) {
                return -1;
            }

            if ('' === $a->getHost() && '' !== $b->getHost()) {
                return 1;
            }

            /** @var PageModel $pageA */
            $pageA = $a->getDefault('pageModel');

            /** @var PageModel $pageB */
            $pageB = $b->getDefault('pageModel');

            // TODO Check if this is really necessary, as routes are generated from pages so pageModel is always there
            if (!$pageA instanceof PageModel || !$pageB instanceof PageModel) {
                return 0;
            }

            if ('root' !== $pageA->type && 'root' === $pageB->type) {
                return -1;
            }

            if ('root' === $pageA->type && 'root' !== $pageB->type) {
                return 1;
            }

            if (null !== $languages && $pageA->rootLanguage !== $pageB->rootLanguage) {
                $langA = $languages[$pageA->rootLanguage] ?? null;
                $langB = $languages[$pageB->rootLanguage] ?? null;

                if (null === $langA && null === $langB) {
                    if ($pageA->rootIsFallback) {
                        return -1;
                    }

                    if ($pageB->rootIsFallback) {
                        return 1;
                    }

                    return $pageA->rootSorting <=> $pageB->rootSorting;
                }

                if (null === $langA && null !== $langB) {
                    return 1;
                }

                if (null !== $langA && null === $langB) {
                    return -1;
                }

                return $langA < $langB ? -1 : 1;
            }

            return strnatcasecmp((string) $pageB->alias, (string) $pageA->alias);
        });
    }

    /**
     * @return Model[]
     */
    private function findPages(array $candidates): array
    {
        $ids = [];
        $aliases = [];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate)) {
                $ids[] = (int) $candidate;
            } else {
                $aliases[] = $this->database->quote($candidate);
            }
        }

        $conditions = [];

        if (!empty($ids)) {
            $conditions[] = 'tl_page.id IN ('.implode(',', $ids).')';
        }

        if (!empty($aliases)) {
            $conditions[] = 'tl_page.alias IN ('.implode(',', $aliases).')';
        }

        /** @var PageModel $pageModel */
        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findBy([implode(' OR ', $conditions)], []);

        if ($pages instanceof Collection) {
            return $pages->getModels();
        }

        return [];
    }

    /**
     * @return Model[]
     */
    private function findRootPages(string $httpHost): array
    {
        if (
            !empty($GLOBALS['TL_HOOKS']['getRootPageFromUrl'])
            && \is_array($GLOBALS['TL_HOOKS']['getRootPageFromUrl'])
        ) {
            /** @var System $system */
            $system = $this->framework->getAdapter(System::class);

            foreach ($GLOBALS['TL_HOOKS']['getRootPageFromUrl'] as $callback) {
                $page = $system->importStatic($callback[0])->{$callback[1]}();

                if ($page instanceof PageModel) {
                    return [$page];
                }
            }
        }

        $rootPages = [];
        $indexPages = [];

        /** @var PageModel $pageModel */
        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findBy(["(tl_page.type='root' AND (tl_page.dns=? OR tl_page.dns=''))"], $httpHost);

        if ($pages instanceof Collection) {
            $rootPages = $pages->getModels();
        }

        $pages = $pageModel->findBy(["tl_page.alias='index' OR tl_page.alias='/'"], null);

        if ($pages instanceof Collection) {
            $indexPages = $pages->getModels();
        }

        return array_merge($rootPages, $indexPages);
    }
}
