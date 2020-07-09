<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Page;

use Contao\PageModel;
use Symfony\Component\Routing\Route;

class PageRegistry
{
    /**
     * @var array
     */
    private $routeConfigs = [];

    /**
     * @var array<PageRouteEnhancerInterface>
     */
    private $routeEnhancers = [];

    /**
     * @var array<CompositionAwareInterface>
     */
    private $compositionAware = [];

    public function hasRouteConfig(string $type): bool
    {
        return isset($this->routeConfigs[$type]);
    }

    public function getRouteConfig(string $type): ?RouteConfig
    {
        return $this->routeConfigs[$type] ?? null;
    }

    public function enhancePageRoute(PageRoute $route): Route
    {
        $type = $route->getPageModel()->type;

        if (!isset($this->routeEnhancers[$type])) {
            return $route;
        }

        /** @var PageRouteEnhancerInterface $enhancer */
        $enhancer = $this->routeEnhancers[$type];

        return $enhancer->enhancePageRoute($route);
    }

    public function getUrlSuffixes(): array
    {
        $urlSuffixes = [];

        foreach ($this->routeEnhancers as $enhancer) {
            $urlSuffixes[] = $enhancer->getUrlSuffixes();
        }

        return array_filter(array_unique(array_merge(...$urlSuffixes)));
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        if (!isset($this->compositionAware[$pageModel->type])) {
            return true;
        }

        /** @var CompositionAwareInterface $service */
        $service = $this->compositionAware[$pageModel->type];

        return $service->supportsContentComposition($pageModel);
    }

    public function add(string $type, RouteConfig $config, PageRouteEnhancerInterface $routeEnhancer = null, CompositionAwareInterface $compositionAware = null): self
    {
        // Override existing pages with the same identifier
        $this->routeConfigs[$type] = $config;

        if (null !== $routeEnhancer) {
            $this->routeEnhancers[$type] = $routeEnhancer;
        }

        if (null !== $compositionAware) {
            $this->compositionAware[$type] = $compositionAware;
        }

        return $this;
    }

    public function remove(string $type): self
    {
        unset(
            $this->routeConfigs[$type],
            $this->routeEnhancers[$type],
            $this->compositionAware[$type]
        );

        return $this;
    }

    public function keys(): array
    {
        return array_keys($this->routeConfigs);
    }
}
