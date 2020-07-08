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

/**
 * A page route provider provides routing information for a page type.
 * The route provider knows what route for a page of its type looks like, e.g. if
 * a particular route has a different URL suffix than configured in the root page.
 */
interface PageRouteProviderInterface
{
    /**
     * While matching URLs, contao generates alias candidates and looks for matching page models.
     * Based on these page's "type" property, the respective provider is asked for the route,
     * as only the route provider knows about requirements and defaults.
     *
     * To generate URLs for a page, the respective route provider needs to return a route based on
     * the page configuration. If $content is available, it can be used to enhance route defaults,
     * so the route can be generated even if no parameters have been passed to the router generate() method.
     */
    public function getRouteForPage(PageModel $pageModel, $content = null): Route;
}
