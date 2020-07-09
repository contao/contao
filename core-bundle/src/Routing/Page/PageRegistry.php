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
     * @var array<PageRouteProviderInterface>
     */
    private $routeProviders = [];

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

    public function hasRouteProvider(PageModel $pageModel): bool
    {
        return isset($this->routeConfigs[$pageModel->type]);
    }

    public function getRouteForPage(PageModel $pageModel, $content = null): Route
    {
        if (!isset($this->routeProviders[$pageModel->type])) {
            throw new \InvalidArgumentException(
                sprintf('Page of type "%s" does not have a route provider.', $pageModel->type)
            );
        }

        return $this->routeProviders[$pageModel->type]->getRouteForPage($pageModel, $content);
    }

    public function supportsContentComposition(PageModel $pageModel): bool
    {
        if (!isset($this->compositionAware[$pageModel->type])) {
            return true;
        }

        return $this->compositionAware[$pageModel->type]->supportsContentComposition($pageModel);
    }

    public function add(string $type, RouteConfig $config, PageRouteProviderInterface $routeProvider = null, CompositionAwareInterface $compositionAware = null): self
    {
        // Override existing pages with the same identifier
        $this->routeConfigs[$type] = $config;

        if (null !== $routeProvider) {
            $this->routeProviders[$type] = $routeProvider;
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
            $this->routeProviders[$type],
            $this->compositionAware[$type]
        );

        return $this;
    }

    public function keys(): array
    {
        return array_keys($this->routeConfigs);
    }
}
