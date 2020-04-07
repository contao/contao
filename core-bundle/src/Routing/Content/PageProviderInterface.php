<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Content;

use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * A page provider provides information about a page type.
 * The page provider knows what route for a page of its type looks like, e.g. if
 * a particular route has a different URL suffix than configured in the root page.
 */
interface PageProviderInterface
{
    public const ATTRIBUTE = '_page_provider';

    /**
     * While matching URLs, contao generates alias candidates and looks for matching page models.
     * Based on these page's "type" property, the respective page provider is asked for the route,
     * as only the page provider knows about requirements and defaults.
     *
     * To generate URLs for a page, the respective page provider needs to return a route based on
     * the page configuration. If $content is available, it can be used to enhance route defaults,
     * so the route can be generated even if no parameters have been passed to the router generate() method.
     *
     * When matching URLs, $content is empty and $request is present. When generating URLs,
     * $content _might_ be there, but there's never a "current" $request.
     *
     * @param mixed $content
     */
    public function getRouteForPage(PageModel $pageModel, $content = null, Request $request = null): Route;

    /**
     * The URL suffixes are used to generate alias candidates, which means
     * stripping the URL suffix from a request path to find pages with
     * the remaining path as an alias.
     *
     * Only return a non-empty array if this page does not use the global
     * URL suffix as configured in the root page, e.g if this is a "feed" page type,
     * it could return ".rss" and ".atom" as the URL suffixes.
     */
    public function getUrlSuffixes(): array;

    /**
     * If the page supports content composition, it's layout is defined by a Contao
     * page layout, and it supports articles and content elements.
     *
     * Most Contao page types do support composition. Pages that do not support composition
     * can be structural (e.g. a redirect page) or functional (e.g. an XML sitemap).
     *
     * The optional $pageModel might tell if a particular page supports composition,
     * for example a 404 page that redirects cannot have articles, but a regular 404 does.
     */
    public function supportContentComposition(PageModel $pageModel = null): bool;

    /**
     * Gets the page type of this provider.
     */
    public static function getPageType(): string;
}
