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

use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler;

class UnroutablePageRouteCompiler extends RouteCompiler
{
    public static function compile(Route $route)
    {
        if ($route instanceof PageRoute) {
            throw new RouteNotFoundException(sprintf('Cannot create route for page type "%s"', $route->getPageModel()->type));
        }

        return parent::compile($route);
    }
}
