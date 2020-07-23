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

use Contao\CoreBundle\Exception\ContentRouteNotFoundException;
use Contao\CoreBundle\Routing\Content\ContentRouteProviderInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Symfony\Component\Routing\Route;

class RouteFactory
{
    /**
     * @var PageRegistry
     */
    private $pageRegistry;

    /**
     * @var array<ContentRouteProviderInterface>
     */
    private $routeProviders;

    public function __construct(PageRegistry $pageRegistry, iterable $routeProviders)
    {
        $this->pageRegistry = $pageRegistry;
        $this->routeProviders = $routeProviders;
    }

    /**
     * Creates a route for page in Contao.
     *
     * If RouteConfig::getPath() is not configured (is null), the route will accept
     * any parameters after the page alias (e.g. "en/page-alias/foo/bar.html").
     *
     * A route enhancer might change or replace the route for a specific page.
     */
    public function createRouteForPage(PageModel $pageModel, string $defaultParameters = '', $content = null): Route
    {
        $config = $this->pageRegistry->getRouteConfig($pageModel->type);
        $path = $config->getPath();
        $defaults = $config->getDefaults();
        $requirements = $config->getRequirements();

        if (null === $path) {
            $path = '/'.($pageModel->alias ?: $pageModel->id).'{!parameters}';
            $defaults['parameters'] = $defaultParameters;
            $requirements['parameters'] = $pageModel->requireItem ? '/.+' : '(/.+)?';
        }

        $route = new PageRoute($pageModel, $path, $defaults, $requirements, $config->getOptions(), $config->getMethods());
        $route->setContent($content);

        if (null !== $config->getUrlSuffix()) {
            $route->setUrlSuffix($config->getUrlSuffix());
        }

        return $this->pageRegistry->enhancePageRoute($route);
    }

    public function createRouteForContent($content): Route
    {
        if ($content instanceof Route) {
            return $content;
        }

        foreach ($this->routeProviders as $provider) {
            if ($provider->supportsContent($content)) {
                return $provider->getRouteForContent($content);
            }
        }

        throw new ContentRouteNotFoundException($content);
    }
}
