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

    /**
     * Creates a route for page in Contao.
     *
     * If $pathParameters are not configured (is null), the route will accept any parameters after
     * the page alias (e.g. "en/page-alias/foo/bar.html").
     *
     * In any other case, $pathParameters will be appended to the path, to support custom parameters.
     * The value of $pathParameter can be configured in the back end through tl_page.parameters.
     *
     * A route enhancer might change or replace the route for a specific page.
     */
    public function createRoute(PageModel $pageModel, string $defaultParameters = '', $content = null): Route
    {
        $config = $this->pageRegistry->getRouteConfig($pageModel->type) ?: new RouteConfig();
        $pathParameters = $config->getPathParameters();
        $defaults = $config->getDefault();
        $requirements = $config->getRequirements();

        if (null === $pathParameters) {
            $pathParameters = '{parameters}';
            $defaults['parameters'] = $defaultParameters;
            $requirements['parameters'] = $pageModel->requireItem ? '/.+' : '(/.+)?';
        } elseif ('' !== $pathParameters && '' !== $pageModel->parameters) {
            $pathParameters = $pageModel->parameters;
        }

        $route = new PageRoute($pageModel, $pathParameters, $defaults, $requirements, $config->getOptions(), $config->getMethods());
        $route->setContent($content);

        return $this->pageRegistry->enhancePageRoute($route);
    }
}
