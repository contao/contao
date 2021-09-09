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
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\System;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteProvider extends AbstractPageRouteProvider
{
    /**
     * @var PageRegistry
     */
    private $pageRegistry;

    /**
     * @var bool
     */
    private $legacyRouting;

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * @internal Do not inherit from this class; decorate the "contao.routing.route_provider" service instead
     */
    public function __construct(ContaoFramework $framework, CandidatesInterface $candidates, PageRegistry $pageRegistry, bool $legacyRouting, bool $prependLocale)
    {
        parent::__construct($framework, $candidates);

        $this->pageRegistry = $pageRegistry;
        $this->legacyRouting = $legacyRouting;
        $this->prependLocale = $prependLocale;
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $this->framework->initialize(true);

        $pathInfo = rawurldecode($request->getPathInfo());

        // The request string must not contain "auto_item" (see #4012)
        if (false !== strpos($pathInfo, '/auto_item/')) {
            return new RouteCollection();
        }

        $routes = [];

        if ('/' === $pathInfo || ($this->legacyRouting && $this->prependLocale && preg_match('@^/([a-z]{2}(-[A-Z]{2})?)/$@', $pathInfo))) {
            $this->addRoutesForRootPages($this->findRootPages($request->getHttpHost()), $routes);

            return $this->createCollectionForRoutes($routes, $request->getLanguages());
        }

        $pages = $this->findCandidatePages($request);

        if (empty($pages)) {
            return new RouteCollection();
        }

        $this->addRoutesForPages($pages, $routes);

        return $this->createCollectionForRoutes($routes, $request->getLanguages());
    }

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

        if (!\array_key_exists($name, $routes)) {
            throw new RouteNotFoundException('Route "'.$name.'" not found');
        }

        return $routes[$name];
    }

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

    /**
     * @param iterable<PageModel> $pages
     */
    private function addRoutesForPages(iterable $pages, array &$routes): void
    {
        foreach ($pages as $page) {
            $this->addRoutesForPage($page, $routes);
        }
    }

    /**
     * @param array<PageModel> $pages
     */
    private function addRoutesForRootPages(array $pages, array &$routes): void
    {
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

        $route = $this->pageRegistry->getRoute($page);
        $routes['tl_page.'.$page->id] = $route;

        $this->addRoutesForRootPage($page, $routes);
    }

    private function addRoutesForRootPage(PageModel $page, array &$routes): void
    {
        if ('root' !== $page->type && 'index' !== $page->alias && '/' !== $page->alias) {
            return;
        }

        $page->loadDetails();
        $route = $this->pageRegistry->getRoute($page);
        $urlPrefix = '';

        if ($route instanceof PageRoute) {
            $urlPrefix = $route->getUrlPrefix();
        }

        $routes['tl_page.'.$page->id.'.root'] = new Route(
            $urlPrefix ? '/'.$urlPrefix.'/' : '/',
            $route->getDefaults(),
            [],
            $route->getOptions(),
            $route->getHost(),
            $route->getSchemes(),
            $route->getMethods()
        );

        if (!$urlPrefix || (!$this->legacyRouting && $page->disableLanguageRedirect)) {
            return;
        }

        $routes['tl_page.'.$page->id.'.fallback'] = new Route(
            '/',
            array_merge(
                $route->getDefaults(),
                [
                    '_controller' => 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction',
                    'path' => '/'.$urlPrefix.'/',
                    'permanent' => false,
                ]
            ),
            [],
            $route->getOptions(),
            $route->getHost(),
            $route->getSchemes(),
            $route->getMethods()
        );
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
            $languages = $this->convertLanguagesForSorting($languages);
        }

        uasort(
            $routes,
            function (Route $a, Route $b) use ($languages, $routes) {
                $nameA = array_search($a, $routes, true);
                $nameB = array_search($b, $routes, true);

                $fallbackA = 0 === substr_compare($nameA, '.fallback', -9);
                $fallbackB = 0 === substr_compare($nameB, '.fallback', -9);

                if ($fallbackA && !$fallbackB) {
                    return 1;
                }

                if ($fallbackB && !$fallbackA) {
                    return -1;
                }

                return $this->compareRoutes($a, $b, $languages);
            }
        );
    }

    /**
     * @return array<PageModel>
     */
    private function findRootPages(string $httpHost): array
    {
        if (
            $this->legacyRouting
            && !empty($GLOBALS['TL_HOOKS']['getRootPageFromUrl'])
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
            /** @var array<PageModel> $rootPages */
            $rootPages = $pages->getModels();
        }

        $pages = $pageModel->findBy(["tl_page.alias='index' OR tl_page.alias='/'"], null);

        if ($pages instanceof Collection) {
            /** @var array<PageModel> $indexPages */
            $indexPages = $pages->getModels();
        }

        return array_merge($rootPages, $indexPages);
    }
}
