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
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Route404Provider implements RouteProviderInterface
{
    use CandidatePagesTrait;

    /**
     * @var RouteFactory
     */
    private $routeFactory;

    public function __construct(ContaoFramework $framework, Connection $connection, CandidatesInterface $candidates, RouteFactory $routeFactory)
    {
        $this->framework = $framework;
        $this->connection = $connection;
        $this->candidates = $candidates;
        $this->routeFactory = $routeFactory;
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $this->framework->initialize(true);

        $collection = new RouteCollection();

        $routes = array_merge(
            $this->getNotFoundRoutes(),
            $this->getLocaleFallbackRoutes($request)
        );

        $this->sortRoutes($routes, $request->getLanguages());

        foreach ($routes as $name => $route) {
            $collection->add($name, $route);
        }

        return $collection;
    }

    public function getRouteByName($name): Route
    {
        throw new RouteNotFoundException('This router cannot load routes by name');
    }

    public function getRoutesByNames($names): array
    {
        // Support console and web inspector profiling
        if (null === $names) {
            $routes = array_merge(
                $this->getNotFoundRoutes(),
                $this->getLocaleFallbackRoutes()
            );

            $this->sortRoutes($routes);

            return $routes;
        }

        return [];
    }

    private function getNotFoundRoutes(): array
    {
        $this->framework->initialize(true);

        /** @var PageModel $pageModel */
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
        try {
            $page->loadDetails();

            if (!$page->rootId) {
                return;
            }
        } catch (NoRootPageFoundException $e) {
            return;
        }

        $defaults = [
            '_token_check' => true,
            '_controller' => 'Contao\FrontendIndex::renderPage',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
            '_locale' => $page->rootLanguage,
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
            $page->rootUseSSL ? 'https' : null
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
            $page->rootUseSSL ? 'https' : null
        );
    }

    private function getLocaleFallbackRoutes(Request $request = null): array
    {
        if (null !== $request && '/' === $request->getPathInfo()) {
            return [];
        }

        $routes = [];

        if (null === $request) {
            /** @var PageModel $pageAdapter */
            $pageAdapter = $this->framework->getAdapter(PageModel::class);
            $pages = $pageAdapter->findAll();
        } else {
            $pages = $this->findCandidatePages($request);
        }

        foreach ($pages as $page) {
            $route = $this->routeFactory->createRouteForPage($page);

            if ($route instanceof PageRoute) {
                $this->addLocaleRedirectRoute($route, $request, $routes);
            }
        }

        return $routes;
    }

    private function addLocaleRedirectRoute(PageRoute $route, ?Request $request, array &$routes): void
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

        if (null !== $request) {
            $path = '/'.$route->getUrlPrefix().$request->getPathInfo();
        }

        $redirect->addDefaults([
            '_controller' => RedirectController::class,
            'path' => $path,
            'permanent' => true,
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
    private function sortRoutes(array &$routes, array $languages = null): void
    {
        // Convert languages array so key is language and value is priority
        if (null !== $languages) {
            foreach ($languages as &$language) {
                $language = str_replace('_', '-', $language);

                if (5 === \strlen($language)) {
                    $lng = substr($language, 0, 2);

                    // Append the language if only language plus dialect is given (see #430)
                    if (!\in_array($lng, $languages, true)) {
                        $languages[] = $lng;
                    }
                }
            }

            unset($language);

            $languages = array_flip(array_values($languages));
        }

        uasort(
            $routes,
            static function (Route $a, Route $b) use ($languages, $routes) {
                $errorA = false !== strpos('.error_404', array_search($a, $routes, true));
                $errorB = false !== strpos('.error_404', array_search($a, $routes, true), -7);
                $localeA = '.locale' === substr(array_search($a, $routes, true), -7);
                $localeB = '.locale' === substr(array_search($b, $routes, true), -7);

                if ($errorA && !$errorB) {
                    return 1;
                }

                if ($errorB && !$errorA) {
                    return -1;
                }

                if ($localeA && !$localeB) {
                    return -1;
                }

                if ($localeB && !$localeA) {
                    return 1;
                }

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

                if (!$pageA instanceof PageModel || !$pageB instanceof PageModel) {
                    return 0;
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
            }
        );
    }
}
