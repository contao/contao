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
     * @var PageRegistry
     */
    private $pageRegistry;

    public function __construct(PageRegistry $pageRegistry)
    {
        $this->pageRegistry = $pageRegistry;
    }

    public function createRoute(PageModel $pageModel, $content = null): Route
    {
        if (null !== ($route = $this->getRouteFromProvider($pageModel, $content))) {
            return $route;
        }

        return $this->getRouteFromConfig($pageModel, $content);
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

    private function getRouteFromProvider(PageModel $pageModel, $content = null): ?Route
    {
        try {
            if ($this->pageRegistry->hasRouteProvider($pageModel)) {
                return $this->pageRegistry->getRouteForPage($pageModel, $content);
            }
        } catch (RouteNotFoundException $e) {
            return null;
        }

        return null;
    }

    private function getRouteFromConfig(PageModel $pageModel, $content = null): PageRoute
    {
        $config = $this->pageRegistry->getRouteConfig($pageModel->type) ?: new RouteConfig();

        $route = new PageRoute($pageModel, $config->getDefault(), $config->getRequirements(), $config->getOptions(), $config->getMethods());
        $route->setContent($content);

        return $route;
    }
}
