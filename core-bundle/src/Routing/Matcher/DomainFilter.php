<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Matcher;

use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Removes routes with a different or no hostname if there are routes for the
 * current hostname. This prevents the fallback (empty) domain from matching if a
 * root page for the current domain exists.
 */
class DomainFilter implements RouteFilterInterface
{
    public function filter(RouteCollection $collection, Request $request): RouteCollection
    {
        $hasDomainMatch = false;
        $httpHost = $request->getHttpHost();

        foreach ($collection->all() as $route) {
            $host = $route->getHost();

            if ($host && $host === $httpHost) {
                $hasDomainMatch = true;
                break;
            }
        }

        if ($hasDomainMatch) {
            foreach ($collection->all() as $name => $route) {
                $host = $route->getHost();

                if (!$host || $host !== $httpHost) {
                    $collection->remove($name);
                }
            }
        }

        return $collection;
    }
}
