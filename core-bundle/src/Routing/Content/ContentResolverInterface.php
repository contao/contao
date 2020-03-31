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

use Symfony\Component\Routing\Route;

interface ContentResolverInterface
{
    public const ATTRIBUTE = '_content_resolver';

    /**
     * Returns a Symfony\Component\Routing\Route or another $content to resolve.
     *
     * @param mixed $content The route "content" which may also be an object or anything
     *
     * @return Route|mixed
     */
    public function resolveContent($content);

    /**
     * Whether this provider supports the supplied $content.
     *
     * This check does not need to look if the specific instance can be
     * resolved to a route, only whether the router can generate routes from
     * objects of this class.
     *
     * @param mixed $content The route "content" which may also be an object or anything
     */
    public function supportsContent($content): bool;
}
