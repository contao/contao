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

use Symfony\Component\Routing\Route;

/**
 * A page can dynamically adjust the route for a page at runtime.
 */
interface DynamicRouteInterface
{
    /**
     * While matching URLs, Contao generates alias candidates and looks for
     * matching page models. Based on the page's "type" property, the route
     * factory creates a default route and asks to enhance the route, as only
     * the route provider knows about dynamic requirements or defaults.
     *
     * To generate URLs for a page, the respective route enhancer needs to
     * return a route based on the page configuration. If content is available,
     * it can be used to enhance route defaults, so the route can be generated
     * even if no parameters have been passed to the router generate() method.
     */
    public function enhancePageRoute(PageRoute $route): Route;

    /**
     * URL suffixes are used to generate alias candidates, which means
     * stripping the URL suffix from a request path to find pages with the
     * remaining path as an alias.
     *
     * Only return a non-empty array if this page does not use the global URL
     * suffix as configured in the root page, e.g. if this is a "feed" page
     * type, it could return ".rss" and ".atom" as URL suffixes.
     */
    public function getUrlSuffixes(): array;
}
