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

use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCompiler;

class PageRouteCompiler extends RouteCompiler
{
    #[\Override]
    public static function compile(Route $route): CompiledRoute
    {
        if (!$route instanceof PageRoute || '' === $route->getUrlSuffix()) {
            return parent::compile($route);
        }

        // Remove the URL suffix from the path to allow paths without optional parameters
        $urlSuffix = $route->getUrlSuffix();
        $route->setUrlSuffix('');

        $compiledRoute = parent::compile($route);

        // Re-add the URL suffix to the original route
        $route->setUrlSuffix($urlSuffix);

        // Make last pattern before suffix non-possessive
        $regex = $compiledRoute->getRegex();
        $lastParam = strrpos($regex, '[^/]++');

        if (false !== $lastParam) {
            $regex = substr_replace($regex, '[^/]+?', $lastParam, 6);
        }

        // Manually add the URL suffix to regex and path tokens
        $regex = preg_replace('/^{\^([^$]+)\$}/', '{^$1'.preg_quote($urlSuffix, null).'$}', $regex);
        $tokens = $compiledRoute->getTokens();
        array_unshift($tokens, ['text', $urlSuffix]);

        return new CompiledRoute(
            $compiledRoute->getStaticPrefix(),
            $regex,
            $tokens,
            $compiledRoute->getPathVariables(),
            $compiledRoute->getHostRegex(),
            $compiledRoute->getHostTokens(),
            $compiledRoute->getHostVariables(),
            $compiledRoute->getVariables(),
        );
    }
}
