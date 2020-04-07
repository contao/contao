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
 * A content URL resolver converts content to a URL (represented by a route object).
 *
 * There are multiple valid results for a resolver:
 *
 * 1. Resolve to a Contao\CoreBundle\Routing\Content\PageRoute if the
 *    content is embedded on a page (e.g. through a "reader" module).
 *
 * 2. Resolve to a Symfony\Component\Routing\Route if the content's URL
 *    is not within the CMS (e.g. an absolute URL).
 *
 * 3. Resolve to a different content that represents this content
 *    (e.g. if the content has no "full" representation of its own).
 *
 * If a resolver is responsible for a content object but cannot convert it to a route,
 * a Symfony\Component\Routing\Exception\RouteNotFoundException should be thrown.
 */
interface ContentUrlResolverInterface
{
    public const ATTRIBUTE = '_content_resolver';

    /**
     * Returns a Symfony\Component\Routing\Route or another $content to resolve.
     *
     * @param mixed $content The route "content" which may be an object or anything
     *
     * @return Route|PageRoute|mixed
     *
     * @throws RouteNotFoundException if the content cannot be resolved
     */
    public function resolveContent($content);

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
