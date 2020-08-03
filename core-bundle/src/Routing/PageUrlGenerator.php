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

use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PageUrlGenerator extends SymfonyUrlGenerator
{
    /**
     * @var RouteProviderInterface
     */
    private $provider;

    /**
     * @var PageRegistry
     */
    private $pageRegistry;

    public function __construct(RouteProviderInterface $provider, PageRegistry $pageRegistry, LoggerInterface $logger = null)
    {
        parent::__construct(new RouteCollection(), new RequestContext(), $logger);

        $this->provider = $provider;
        $this->pageRegistry = $pageRegistry;
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param int    $referenceType
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (RouteObjectInterface::OBJECT_BASED_ROUTE_NAME === $name) {
            $route = $this->getRouteFromParameters($parameters);
        } elseif (null === $route = $this->provider->getRouteByName($name)) {
            throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', $name));
        }

        // The route has a cache of its own and is not recompiled as long as it does not get modified
        /** @var CompiledRoute $compiledRoute */
        $compiledRoute = $route->compile();

        if (
            $route instanceof PageRoute
            && 0 === \count(array_intersect_key(array_filter($parameters), array_flip($compiledRoute->getVariables())))
        ) {
            $staticPrefix = $compiledRoute->getStaticPrefix();
            $indexPath = ($route->getUrlPrefix() ? '/'.$route->getUrlPrefix() : '').'/index';

            if ($staticPrefix === $indexPath || $staticPrefix === $indexPath.$route->getUrlSuffix()) {
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

    private function getRouteFromParameters(array &$parameters): Route
    {
        if (
            array_key_exists(RouteObjectInterface::ROUTE_OBJECT, $parameters)
            && $parameters[RouteObjectInterface::ROUTE_OBJECT] instanceof Route
        ) {
            $route = $parameters[RouteObjectInterface::ROUTE_OBJECT];
            unset($parameters[RouteObjectInterface::ROUTE_OBJECT]);

            return $route;
        }

        if (
            array_key_exists(RouteObjectInterface::CONTENT_OBJECT, $parameters)
            && $parameters[RouteObjectInterface::CONTENT_OBJECT] instanceof PageModel
        ) {
            $route = $this->pageRegistry->getRoute($parameters[RouteObjectInterface::CONTENT_OBJECT]);
            unset($parameters[RouteObjectInterface::CONTENT_OBJECT]);

            return $route;
        }

        throw new RouteNotFoundException('Route "'.RouteObjectInterface::OBJECT_BASED_ROUTE_NAME.'" requires PageModel or route object parameter.');
    }
}
