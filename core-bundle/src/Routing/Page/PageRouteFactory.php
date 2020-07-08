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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class PageRouteFactory
{
    /**
     * @var array
     */
    private $routeConfigs = [];

    /**
     * @var array<PageRouteProviderInterface>
     */
    private $routeProviders = [];

    public function createRoute(PageModel $pageModel, $content = null): Route
    {
        if (null !== ($route = $this->getRouteFromProvider($pageModel, $content))) {
            return $route;
        }

        $route = new PageRoute($pageModel, $this->routeConfigs[$pageModel->type] ?? []);
        $route->setContent($content);

        return $route;
    }

    public function createRouteWithParameters(PageModel $pageModel, string $parameters = '', $content = null): Route
    {
        if (null !== ($route = $this->getRouteFromProvider($pageModel, $content))) {
            return $route;
        }

        $route = $this->getRouteFromConfig($pageModel, $content);
        $route->setPath(sprintf('/%s{parameters}', $pageModel->alias ?: $pageModel->id));
        $route->setDefault('parameters', $parameters);
        $route->setRequirement('parameters', $pageModel->requireItem ? '/.+' : '(/.+)?');

        return $route;
    }

    public function add(string $type, RouteConfig $config, PageRouteProviderInterface $routeProvider = null): self
    {
        // Override existing pages with the same identifier
        $this->routeConfigs[$type] = $config;

        if (null !== $routeProvider) {
            $this->routeProviders[$type] = $routeProvider;
        }

        return $this;
    }

    public function remove(string $type): self
    {
        unset($this->routeConfigs[$type], $this->routeProviders[$type]);

        return $this;
    }

    public function has(string $type): bool
    {
        return isset($this->routeConfigs[$type]);
    }

    public function getPageTypes(): array
    {
        return array_keys($this->routeConfigs);
    }

    private function getRouteFromProvider(PageModel $pageModel, $content = null): ?Route
    {
        try {
            $provider = $this->routeProviders[$pageModel->type] ?? null;

            if ($provider instanceof PageRouteProviderInterface) {
                return $provider->getRouteForPage($pageModel, $content);
            }
        } catch (RouteNotFoundException $e) {
            return null;
        }

        return null;
    }

    private function getRouteFromConfig(PageModel $pageModel, $content = null): PageRoute
    {
        $config = $this->routeConfigs[$pageModel->type] ?? new RouteConfig();

        $route = new PageRoute($pageModel, $config->getDefault(), $config->getRequirements(), $config->getOptions(), $config->getMethods());
        $route->setContent($content);

        return $route;
    }
}
