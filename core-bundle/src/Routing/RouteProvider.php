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
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
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
     * @var ContaoFrameworkInterface
     */
    private $framework;

    /**
     * @var Connection
     */
    private $database;

    /**
     * @var PageModel
     */
    private $pageAdapter;

    /**
     * @var Config
     */
    private $configAdapter;

    public function __construct(ContaoFrameworkInterface $framework, Connection $database)
    {
        $this->framework = $framework;
        $this->database = $database;

        $this->pageAdapter = $framework->getAdapter(PageModel::class);
        $this->configAdapter = $this->framework->getAdapter(Config::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $this->framework->initialize();
        $pathInfo = $request->getPathInfo();

        // The request string must not contain "auto_item" (see #4012)
        if (false !== strpos($pathInfo, '/auto_item/')) {
            return new RouteCollection();
        }

        if ('/' === $pathInfo
            || ($this->configAdapter->get('addLanguageToUrl') && preg_match('@^/([a-z]{2}(-[A-Z]{2})?)/$@', $pathInfo))
        ) {
            $routes = [];

            $this->addRoutesForRootPages($this->findRootPages(), $routes);

            return $this->createCollectionForRoutes($routes);
        }

        $pathInfo = $this->removeSuffixAndLanguage($pathInfo);

        if (null === $pathInfo) {
            return new RouteCollection();
        }

        $routes = [];
        $candidates = $this->getAliasCandidates($pathInfo);
        $pages = $this->findPages($candidates);

        $this->addRoutesForPages($pages, $routes);

        return $this->createCollectionForRoutes($routes);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name)
    {
        $this->framework->initialize();

        $ids = $this->getPageIdsFromNames([$name]);

        if (empty($ids)) {
            throw new RouteNotFoundException('Route name does not match a page ID');
        }

        $routes = [];
        $page = $this->pageAdapter->findByPk($ids[0]);

        if (null === $page) {
            throw new RouteNotFoundException('Page ID '.$ids[0].' not found');
        }

        $this->addRoutesForPage($page, $routes);

        return $routes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names)
    {
        $this->framework->initialize();

        if (null === $names) {
            $pages = $this->pageAdapter->findAll();
        } else {
            $ids = $this->getPageIdsFromNames($names);

            if (empty($ids)) {
                return [];
            }

            $pages = $this->pageAdapter->findBy('tl_page.id IN ('.implode(',', $ids).')', []);
        }

        $routes = [];

        $this->addRoutesForPages($pages, $routes);
        $this->sortRoutes($routes);

        return $routes;
    }

    private function removeSuffixAndLanguage(string $pathInfo)
    {
        $urlSuffix = $this->configAdapter->get('urlSuffix');
        $suffixLength = \strlen($urlSuffix);

        if (0 !== $suffixLength) {
            if (substr($pathInfo, -$suffixLength) !== $urlSuffix) {
                return null;
            }

            $pathInfo = substr($pathInfo, 0, -$suffixLength);
        }

        if (0 === strncmp($pathInfo, '/', 1)) {
            $pathInfo = substr($pathInfo, 1);
        }

        if ($this->configAdapter->get('addLanguageToUrl')) {
            $matches = [];

            if (preg_match('@^([a-z]{2}(-[A-Z]{2})?)/(.+)$@', $pathInfo, $matches)) {
                $pathInfo = $matches[3];
            } else {
                return null;
            }
        }

        return $pathInfo;
    }

    /**
     * Compile all possible aliases by applying dirname() to the request (e.g. news/archive/item, news/archive, news).
     *
     *
     * @return array
     */
    private function getAliasCandidates(string $pathInfo)
    {
        $pos = strpos($pathInfo, '/');

        if (false === $pos) {
            return [$pathInfo];
        }

        if (!$this->configAdapter->get('folderUrl')) {
            return [substr($pathInfo, 0, $pos)];
        }

        $candidates = [$pathInfo];

        while ('/' !== $pathInfo && false !== strpos($pathInfo, '/')) {
            $pathInfo = \dirname($pathInfo);
            $candidates[] = $pathInfo;
        }

        return $candidates;
    }

    private function addRoutesForPages($pages, array &$routes): void
    {
        if (null === $pages) {
            return;
        }

        /** @var PageModel $page */
        foreach ($pages as $page) {
            $this->addRoutesForPage($page, $routes);
        }
    }

    private function addRoutesForRootPages(array $pages, array &$routes): void
    {
        if (null === $pages) {
            return;
        }

        /** @var PageModel $page */
        foreach ($pages as $page) {
            $this->addRoutesForRootPage($page, $routes);
        }
    }

    private function createCollectionForRoutes(array $routes): RouteCollection
    {
        $this->sortRoutes($routes);

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

        $path = sprintf('/%s{parameters}%s', $page->alias ?: $page->id, $this->configAdapter->get('urlSuffix'));

        if ($this->configAdapter->get('addLanguageToUrl')) {
            $path = '/{_locale}'.$path;
//            $requirements['_locale'] = '[a-z]{2}(\-[A-Z]{2})?';
            $requirements['_locale'] = $page->rootLanguage;
        }

        $routes['tl_page.'.$page->id] = new Route(
            $path,
            $defaults,
            $requirements,
            ['utf8' => true],
            $page->domain ?: null,
            $page->rootUseSSL ? 'https' : null // TODO should we match SSL only if enabled in root?
        );

        $this->addRoutesForRootPage($page, $routes);
    }

    private function addRoutesForRootPage(PageModel $page, array &$routes): void
    {
        if ('root' !== $page->type && 'index' !== $page->alias) {
            return;
        }

        $page->loadDetails();

        if (!$this->configAdapter->get('addLanguageToUrl') && 'index' !== $page->alias && !$page->rootIsFallback) {
            return;
        }

        $path = '/';
        $requirements = [];
        $defaults = $this->getRouteDefaults($page);

        if ($this->configAdapter->get('addLanguageToUrl')) {
            $path = '/{_locale}'.$path;
            $requirements['_locale'] = $page->rootLanguage;
        }

        $routes['tl_page.'.$page->id.'.root'] = new Route(
            $path,
            $defaults,
            $requirements,
            [],
            $page->domain ?: null,
            $page->rootUseSSL ? 'https' : null // TODO should we match SSL only if enabled in root?
        );

        if ($this->configAdapter->get('addLanguageToUrl') && $page->rootIsFallback) {
            if (!$this->configAdapter->get('doNotRedirectEmpty')) {
                $defaults['_controller'] = 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction';
                $defaults['path'] = '/'.$page->language.'/';
                $defaults['permanent'] = true;
            }

            $routes['tl_page.'.$page->id.'.fallback'] = new Route(
                '/',
                $defaults,
                [],
                [],
                $page->domain ?: null,
                $page->rootUseSSL ? 'https' : null // TODO should we match SSL only if enabled in root?
            );
        }
    }

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

    private function getPageIdsFromNames(array $names)
    {
        $ids = [];

        foreach ($names as $name) {
            if (0 !== strncmp($name, 'tl_page.', 8)) {
                continue;
            }

            [, $id] = explode('.', $name);

            $ids[] = $id;
        }

        return array_unique($ids);
    }

    /**
     * Sorts routes so that the FinalMatcher will correctly resolve them.
     * 1. The ones with hostname should come first so the empty ones are only taken if no hostname matches
     * 2. Root pages come last so non-root page with index alias (= identical path) matches first
     * 3. Pages with longer alias (folder page) must come first to match if applicable.
     */
    private function sortRoutes(array &$routes): void
    {
        uasort($routes, function (Route $a, Route $b) {
            if ('' !== $a->getHost() && '' === $b->getHost()) {
                return -1;
            }

            if ('' === $a->getHost() && '' !== $b->getHost()) {
                return 1;
            }

            $pageA = $a->getDefault('pageModel');
            $pageB = $b->getDefault('pageModel');

            if (!$pageA instanceof PageModel || !$pageB instanceof PageModel) {
                return 0;
            }

            if ('root' !== $pageA->type && 'root' === $pageB->type) {
                return -1;
            }

            if ('root' === $pageA->type && 'root' !== $pageB->type) {
                return 1;
            }

            return strnatcasecmp($pageB->alias, $pageA->alias);
        });
    }

    private function findPages(array $candidates)
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

        $table = $this->pageAdapter->getTable();
        $conditions = [];

        if (!empty($ids)) {
            $conditions[] = $table.'.id IN ('.implode(',', $ids).')';
        }

        if (!empty($aliases)) {
            $conditions[] = $table.'.alias IN ('.implode(',', $aliases).')';
        }

        $pages = $this->pageAdapter->findBy([implode(' OR ', $conditions)], []);

        if ($pages instanceof Collection) {
            return $pages->getModels();
        }

        return [];
    }

    private function findRootPages(): array
    {
        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getRootPageFromUrl']) && \is_array($GLOBALS['TL_HOOKS']['getRootPageFromUrl'])) {
            /** @var System $systemAdapter */
            $systemAdapter = $this->framework->getAdapter(System::class);

            foreach ($GLOBALS['TL_HOOKS']['getRootPageFromUrl'] as $callback) {
                $page = $systemAdapter->importStatic($callback[0])->{$callback[1]}();

                /** @var PageModel $page */
                if ($page instanceof PageModel) {
                    return [$page];
                }
            }
        }

        // Include pages with alias "index" or "/" (see #8498, #8560 and #1210)
        $pages = $this->pageAdapter->findBy(["tl_page.type='root' OR tl_page.alias='index' OR tl_page.alias='/'"], []);

        if ($pages instanceof Collection) {
            return $pages->getModels();
        }

        return [];
    }
}
