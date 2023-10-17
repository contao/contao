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

use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\Model\Collection;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteProvider extends AbstractPageRouteProvider
{
    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $this->framework->initialize();

        $pathInfo = rawurldecode($request->getPathInfo());

        // The request string must start with "/" and must not contain "auto_item" (see #4012)
        if (!str_starts_with($pathInfo, '/') || str_contains($pathInfo, '/auto_item/')) {
            return new RouteCollection();
        }

        $routes = [];

        if ('/' === $pathInfo) {
            $this->addRoutesForRootPages($this->findRootPages($request->getHttpHost()), $routes);

            return $this->createCollectionForRoutes($routes, $request->getLanguages());
        }

        if (!$pages = $this->findCandidatePages($request)) {
            return new RouteCollection();
        }

        $this->addRoutesForPages($pages, $routes);

        return $this->createCollectionForRoutes($routes, $request->getLanguages());
    }

    public function getRouteByName(string $name): Route
    {
        $this->framework->initialize();

        if (!$ids = $this->getPageIdsFromNames([$name])) {
            throw new RouteNotFoundException('Route name does not match a page ID');
        }

        $pageModel = $this->framework->getAdapter(PageModel::class);
        $page = $pageModel->findByPk($ids[0]);

        if (null === $page || !$this->pageRegistry->isRoutable($page)) {
            throw new RouteNotFoundException(sprintf('Page ID "%s" not found', $ids[0]));
        }

        $routes = [];

        $this->addRoutesForPage($page, $routes);

        if (!\array_key_exists($name, $routes)) {
            throw new RouteNotFoundException('Route "'.$name.'" not found');
        }

        return $routes[$name];
    }

    public function getRoutesByNames($names = null): iterable
    {
        $this->framework->initialize();

        $pageModel = $this->framework->getAdapter(PageModel::class);

        if (null === $names) {
            $pages = $pageModel->findAll();
        } else {
            if (!$ids = $this->getPageIdsFromNames($names)) {
                return [];
            }

            $pages = $pageModel->findBy('tl_page.id IN ('.implode(',', $ids).')', []);
        }

        if (!$pages instanceof Collection) {
            return [];
        }

        $routes = [];

        $models = $pages->getModels();
        $models = array_filter($models, fn (PageModel $page): bool => $this->pageRegistry->isRoutable($page));

        $this->addRoutesForPages($models, $routes);
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
            $route = $this->pageRegistry->getRoute($page);
            $this->addRoutesForRootPage($route, $routes);
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
        try {
            $page->loadDetails();

            if (!$page->rootId) {
                return;
            }
        } catch (NoRootPageFoundException) {
            return;
        }

        $route = $this->pageRegistry->getRoute($page);
        $routes['tl_page.'.$page->id] = $route;

        $this->addRoutesForRootPage($route, $routes);
    }

    private function addRoutesForRootPage(PageRoute $route, array &$routes): void
    {
        $page = $route->getPageModel();

        if ('root' !== $page->type && 'index' !== $page->alias && '/' !== $page->alias) {
            return;
        }

        $urlPrefix = $route->getUrlPrefix();

        $routes['tl_page.'.$page->id.'.root'] = new Route(
            $urlPrefix ? '/'.$urlPrefix.'/' : '/',
            $route->getDefaults(),
            [],
            $route->getOptions(),
            $route->getHost(),
            $route->getSchemes(),
            $route->getMethods()
        );

        if (!$urlPrefix || $page->loadDetails()->disableLanguageRedirect) {
            return;
        }

        $routes['tl_page.'.$page->id.'.fallback'] = new Route(
            '/',
            [
                ...$route->getDefaults(),
                '_controller' => 'Symfony\Bundle\FrameworkBundle\Controller\RedirectController::urlRedirectAction',
                'path' => '/'.$urlPrefix.'/',
                'permanent' => false,
            ],
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
    private function sortRoutes(array &$routes, array|null $languages = null): void
    {
        // Convert languages array so key is language and value is priority
        if (null !== $languages) {
            $languages = $this->convertLanguagesForSorting($languages);
        }

        uasort(
            $routes,
            function (Route $a, Route $b) use ($routes, $languages) {
                $nameA = array_search($a, $routes, true);
                $nameB = array_search($b, $routes, true);

                $fallbackA = str_ends_with($nameA, '.fallback');
                $fallbackB = str_ends_with($nameB, '.fallback');

                if ($fallbackA && !$fallbackB) {
                    return 1;
                }

                if ($fallbackB && !$fallbackA) {
                    return -1;
                }

                if ('/' === $a->getPath() && '/' !== $b->getPath()) {
                    return -1;
                }

                if ('/' === $b->getPath() && '/' !== $a->getPath()) {
                    return 1;
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
        $models = [];

        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findBy(["(tl_page.type='root' AND (tl_page.dns=? OR tl_page.dns=''))"], $httpHost);

        if ($pages instanceof Collection) {
            $models = $pages->getModels();
        }

        $pages = $pageModel->findBy(['tl_page.alias=? OR tl_page.alias=?'], ['index', '/']);

        if ($pages instanceof Collection) {
            foreach ($pages as $page) {
                if ($this->pageRegistry->isRoutable($page)) {
                    $models[] = $page;
                }
            }
        }

        return $models;
    }
}
