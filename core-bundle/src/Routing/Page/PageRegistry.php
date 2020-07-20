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
     * @var array<ContentCompositionInterface|bool>
     */
    private $contentComposition = [];

    public function getRouteConfig(string $type): RouteConfig
    {
        return $this->routeConfigs[$type] ?? new RouteConfig();
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

        if (0 === \count($urlSuffixes)) {
            return [];
        }

        return array_unique(array_merge(...$urlSuffixes));
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        if (!isset($this->contentComposition[$pageModel->type])) {
            return true;
        }

        $service = $this->contentComposition[$pageModel->type];

        if ($service instanceof ContentCompositionInterface) {
            return $service->supportsContentComposition($pageModel);
        }

        return (bool) $service;
    }

    /**
     * @param ContentCompositionInterface|bool $contentComposition
     */
    public function add(string $type, RouteConfig $config, PageRouteEnhancerInterface $routeEnhancer = null, $contentComposition = true): self
    {
        // Override existing pages with the same identifier
        $this->routeConfigs[$type] = $config;

        if (null !== $routeEnhancer) {
            $this->routeEnhancers[$type] = $routeEnhancer;
        }

        if (null !== $contentComposition) {
            $this->contentComposition[$type] = $contentComposition;
        }

        return $this;
    }

    public function remove(string $type): self
    {
        unset(
            $this->routeConfigs[$type],
            $this->routeEnhancers[$type],
            $this->contentComposition[$type]
        );

        return $this;
    }

    public function keys(): array
    {
        return array_keys($this->routeConfigs);
    }
}
