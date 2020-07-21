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

use Contao\CoreBundle\Routing\Page\PageRoute;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class ContentResolvingGenerator extends SymfonyUrlGenerator
{
    /**
     * @var RouteFactory
     */
    private $routeFactory;

    public function __construct(RouteFactory $routeFactory, LoggerInterface $logger = null)
    {
        parent::__construct(new RouteCollection(), new RequestContext(), $logger);

        $this->routeFactory = $routeFactory;
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param int    $referenceType
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (PageRoute::ROUTE_NAME !== $name) {
            throw new RouteNotFoundException('Route name is not "'.PageRoute::ROUTE_NAME.'"');
        }

        if (!isset($parameters[PageRoute::CONTENT_PARAMETER])) {
            throw new RouteNotFoundException(sprintf('Missing parameter "%s" for content route (%s).', PageRoute::CONTENT_PARAMETER, PageRoute::ROUTE_NAME));
        }

        $route = $this->routeFactory->createRouteForContent($parameters[PageRoute::CONTENT_PARAMETER]);
        unset($parameters[PageRoute::CONTENT_PARAMETER]);

        // The route has a cache of its own and is not recompiled as long as it does not get modified
        $compiledRoute = $route->compile();

        if (
            $route instanceof PageRoute
            && 0 === \count(array_intersect_key(array_filter($parameters), array_flip($compiledRoute->getVariables())))
        ) {
            $indexPath = ($route->getUrlPrefix() ? '/'.$route->getUrlPrefix() : '').'/index';

            if (
                $compiledRoute->getStaticPrefix() === $indexPath
                || $compiledRoute->getStaticPrefix() === $indexPath.$route->getUrlSuffix()
            ) {
                $route->setPath('/');
                $route->setUrlSuffix('');
                $compiledRoute = $route->compile();
            }
        }

        return $this->doGenerate(
            $compiledRoute->getVariables(),
            $route->getDefaults(),
            $route->getRequirements(),
            $compiledRoute->getTokens(),
            $parameters,
            $name,
            $referenceType,
            $compiledRoute->getHostTokens(),
            $route->getSchemes()
        );
    }
}
