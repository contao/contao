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

/**
 * A page can dynamically adjust the route for a page at runtime.
 */
interface DynamicRouteInterface
{
    /**
     * While matching URLs, Contao generates alias candidates and looks for
     * matching page models. Based on the page's "type" property, the route
     * factory creates a default route and asks to configure the route, as
     * only the page knows about dynamic requirements or defaults.
     *
     * The return type was previously `void` and has changed, we can't add it
     * to the interface for backwards compatibility.
     *
     * @return PageRoute|null
     */
    #[\ReturnTypeWillChange]
    public function configurePageRoute(PageRoute $route);

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
