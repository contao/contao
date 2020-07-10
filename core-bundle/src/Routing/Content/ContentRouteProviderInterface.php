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

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * A content route provider converts content to a URL (represented by a route object).
 *
 * - Resolve to a Contao\CoreBundle\Routing\Page\PageRoute if the
 *   content is embedded on a page (e.g. through a "reader" module).
 *
 * - Resolve to a Symfony\Component\Routing\Route if the content's URL
 *   is not within the CMS (e.g. an absolute URL).
 *
 * If a provider is responsible for a content object but cannot convert it to a route,
 * a Contao\CoreBundle\Exception\ContentRouteNotFoundException should be thrown.
 */
interface ContentRouteProviderInterface
{
    /**
     * @param mixed $content The route "content" which may be an object or anything
     *
     * @throws RouteNotFoundException if the content cannot be resolved
     */
    public function getRouteForContent($content): Route;

    /**
     * Whether this provider supports the supplied $content.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the resolver can generate routes from
     * objects of this class.
     *
     * @param mixed $content The route "content" which may be an object or anything
     */
    public function supportsContent($content): bool;
}
