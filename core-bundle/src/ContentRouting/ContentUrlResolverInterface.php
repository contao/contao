<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\ContentRouting;

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * A content URL resolver converts content to a URL (represented by a route object).
 *
 * - Resolve to a Contao\CoreBundle\ContentRouting\ContentRoute if the
 *    content is embedded on a page (e.g. through a "reader" module).
 *
 * - Resolve to a Symfony\Component\Routing\Route if the content's URL
 *    is not within the CMS (e.g. an absolute URL).
 *
 * If a resolver is responsible for a content object but cannot convert it to a route,
 * a Symfony\Component\Routing\Exception\RouteNotFoundException or
 * Contao\CoreBundle\Exception\ContentRouteNotFoundException should be thrown
 */
interface ContentUrlResolverInterface
{
    public const ATTRIBUTE = '_content_resolver';

    /**
     * @param mixed $content The route "content" which may be an object or anything
     *
     * @throws RouteNotFoundException if the content cannot be resolved
     */
    public function resolveContent($content): Route;

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
