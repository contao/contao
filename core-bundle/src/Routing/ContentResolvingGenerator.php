<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\Exception\ContentRouteNotFoundException;
use Contao\CoreBundle\Routing\Content\ContentRouteProviderInterface;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ContentResolvingGenerator extends SymfonyUrlGenerator
{
    /**
     * @var array<ContentRouteProviderInterface>
     */
    private $routeProviders;

    public function __construct(iterable $routeProviders, LoggerInterface $logger = null)
    {
        parent::__construct(new RouteCollection(), new RequestContext(), $logger);

        $this->routeProviders = $routeProviders;
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param int    $referenceType
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (PageRoute::ROUTE_NAME !== $name || !isset($parameters[PageRoute::CONTENT_PARAMETER])) {
            throw new ContentRouteNotFoundException($name);
        }

        $route = $this->resolveContent($parameters[PageRoute::CONTENT_PARAMETER]);
        unset($parameters[PageRoute::CONTENT_PARAMETER]);

        // the Route has a cache of its own and is not recompiled as long as it does not get modified
        $compiledRoute = $route->compile();

        $debug_message = ContentRouteNotFoundException::getRouteDebugMessage($name);

        return $this->doGenerate($compiledRoute->getVariables(), $route->getDefaults(), $route->getRequirements(), $compiledRoute->getTokens(), $parameters, $debug_message, $referenceType, $compiledRoute->getHostTokens(), $route->getSchemes());
    }

    private function resolveContent($content): Route
    {
        if ($content instanceof Route) {
            return $content;
        }

        foreach ($this->routeProviders as $provider) {
            if ($provider->supportsContent($content)) {
                return $provider->getRouteForContent($content);
            }
        }

        throw new ContentRouteNotFoundException($content);
    }
}
