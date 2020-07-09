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
 * A page route enhancer can adjust the default route for a page.
 * The route enhancer knows what a route for a page looks like, e.g. if
 * a particular route has a different URL suffix than configured in the root page.
 */
interface PageRouteEnhancerInterface
{
    /**
     * While matching URLs, Contao generates alias candidates and looks for matching page models.
     * Based on these page's "type" property, the route factory creates a default route and asks to enhance
     * the route, as only the route provider knows about dynamic requirements or defaults.
     *
     * To generate URLs for a page, the respective route enhancer needs to return a route based on
     * the page configuration. If content is available, it can be used to enhance route defaults,
     * so the route can be generated even if no parameters have been passed to the router generate() method.
     */
    public function enhancePageRoute(PageRoute $route): Route;
}
