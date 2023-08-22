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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Util\LocaleUtil;
use Contao\PageModel;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Route404Provider extends AbstractPageRouteProvider
{
    /**
     * @internal
     */
    public function __construct(ContaoFramework $framework, CandidatesInterface $candidates, PageRegistry $pageRegistry)
    {
        parent::__construct($framework, $candidates, $pageRegistry);
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $this->framework->initialize();

        /** @var array<string, mixed> $routes */
        $routes = [...$this->getNotFoundRoutes(), ...$this->getLocaleFallbackRoutes($request)];

        $this->sortRoutes($routes, $request->getLanguages());

        $collection = new RouteCollection();

        foreach ($routes as $name => $route) {
            $collection->add($name, $route);
        }

        return $collection;
    }

    public function getRouteByName(string $name): Route
    {
        $this->framework->initialize();

        if (!$ids = $this->getPageIdsFromNames([$name])) {
            throw new RouteNotFoundException('Route name does not match a page ID');
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $page = $pageAdapter->findByPk($ids[0]);

        if (!$page) {
            throw new RouteNotFoundException(sprintf('Page ID "%s" not found', $ids[0]));
        }

        $routes = [];

        $this->addNotFoundRoutesForPage($page, $routes);

        if ($this->pageRegistry->isRoutable($page)) {
            $this->addLocaleRedirectRoute($this->pageRegistry->getRoute($page), null, $routes);
        }

        if (!\array_key_exists($name, $routes)) {
            throw new RouteNotFoundException('Route "'.$name.'" not found');
        }

        return $routes[$name];
    }

    public function getRoutesByNames(array|null $names = null): array
    {
        $this->framework->initialize();

        $pageAdapter = $this->framework->getAdapter(PageModel::class);

        if (null === $names) {
            $pages = $pageAdapter->findAll();
        } else {
            if (!$ids = $this->getPageIdsFromNames($names)) {
                return [];
            }

            $pages = $pageAdapter->findBy('tl_page.id IN ('.implode(',', $ids).')', []);
        }

        $routes = [];

        foreach ($pages as $page) {
            $this->addNotFoundRoutesForPage($page, $routes);

            if ($this->pageRegistry->isRoutable($page)) {
                $this->addLocaleRedirectRoute($this->pageRegistry->getRoute($page), null, $routes);
            }
        }

        $this->sortRoutes($routes);

        return $routes;
    }

    private function getNotFoundRoutes(): array
    {
        $this->framework->initialize();

        $pageModel = $this->framework->getAdapter(PageModel::class);
        $pages = $pageModel->findByType('error_404');

        if (null === $pages) {
            return [];
        }

        $routes = [];

        foreach ($pages as $page) {
            $this->addNotFoundRoutesForPage($page, $routes);
        }

        return $routes;
    }

    private function addNotFoundRoutesForPage(PageModel $page, array &$routes): void
    {
        if ('error_404' !== $page->type) {
            return;
        }

        try {
            $page->loadDetails();

            if (!$page->rootId) {
                return;
            }
        } catch (NoRootPageFoundException) {
            return;
        }

        $defaults = [
            '_token_check' => true,
            '_controller' => 'Contao\FrontendIndex::renderPage',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
            '_locale' => LocaleUtil::formatAsLocale($page->rootLanguage ?? ''),
            '_format' => 'html',
            '_canonical_route' => 'tl_page.'.$page->id,
            'pageModel' => $page,
        ];

        $requirements = ['_url_fragment' => '.*'];
        $path = '/{_url_fragment}';

        $routes['tl_page.'.$page->id.'.error_404'] = new Route(
            $path,
            $defaults,
            $requirements,
            ['utf8' => true],
            $page->domain,
            $page->rootUseSSL ? 'https' : 'http'
        );

        if (!$page->urlPrefix) {
            return;
        }

        $path = '/'.$page->urlPrefix.$path;

        $routes['tl_page.'.$page->id.'.error_404.locale'] = new Route(
            $path,
            $defaults,
            $requirements,
            ['utf8' => true],
            $page->domain,
            $page->rootUseSSL ? 'https' : 'http'
        );
    }

    private function getLocaleFallbackRoutes(Request $request): array
    {
        if ('/' === $request->getPathInfo()) {
            return [];
        }

        $routes = [];

        foreach ($this->findCandidatePages($request) as $page) {
            $this->addLocaleRedirectRoute($this->pageRegistry->getRoute($page), $request, $routes);
        }

        return $routes;
    }

    private function addLocaleRedirectRoute(PageRoute $route, Request|null $request, array &$routes): void
    {
        $length = \strlen($route->getUrlPrefix());

        if (0 === $length) {
            return;
        }

        $redirect = new Route(
            substr($route->getPath(), $length + 1),
            $route->getDefaults(),
            $route->getRequirements(),
            $route->getOptions(),
            $route->getHost(),
            $route->getSchemes(),
            $route->getMethods()
        );

        $path = $route->getPath();

        if ($request) {
            $path = '/'.$route->getUrlPrefix().$request->getPathInfo();
        }

        $redirect->addDefaults([
            '_controller' => RedirectController::class,
            'path' => $path,
            'permanent' => false,
        ]);

        $routes['tl_page.'.$route->getPageModel()->id.'.locale'] = $redirect;
    }

    /**
     * Sorts routes so that the FinalMatcher will correctly resolve them.
     *
     * 1. Sort locale-aware routes first, so e.g. /de/not-found.html renders the german error page
     * 2. Then sort by hostname, so the ones with empty host are only taken if no hostname matches
     * 3. Lastly pages must be sorted by accept language and fallback, so the best language matches first
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

                $errorA = str_ends_with($nameA, '.error_404');
                $errorB = str_ends_with($nameB, '.error_404');

                if ($errorA && !$errorB) {
                    return 1;
                }

                if ($errorB && !$errorA) {
                    return -1;
                }

                $localeA = str_ends_with($nameA, '.locale');
                $localeB = str_ends_with($nameB, '.locale');

                if ($localeA && !$localeB) {
                    return -1;
                }

                if ($localeB && !$localeA) {
                    return 1;
                }

                return $this->compareRoutes($a, $b, $languages);
            }
        );
    }
}
