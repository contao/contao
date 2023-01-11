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
use Symfony\Component\Routing\RouteCompilerInterface;

class UnroutablePageRouteCompiler implements RouteCompilerInterface
{
    public static function compile(Route $route): never
    {
        throw new RouteNotFoundException('This route is not routable');
    }
}
