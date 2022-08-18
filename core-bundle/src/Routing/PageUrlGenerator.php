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

use Contao\CoreBundle\Exception\RouteParametersException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\PageModel;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class PageUrlGenerator extends SymfonyUrlGenerator
{
    public function __construct(private RouteProviderInterface $provider, private PageRegistry $pageRegistry, LoggerInterface $logger = null)
    {
        parent::__construct(new RouteCollection(), new RequestContext(), $logger);
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        if (
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME === $name
            && \array_key_exists(RouteObjectInterface::CONTENT_OBJECT, $parameters)
            && $parameters[RouteObjectInterface::CONTENT_OBJECT] instanceof PageModel
        ) {
            $route = $this->pageRegistry->getRoute($parameters[RouteObjectInterface::CONTENT_OBJECT]);
            unset($parameters[RouteObjectInterface::CONTENT_OBJECT]);
        } elseif (
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME === $name
            && \array_key_exists(RouteObjectInterface::ROUTE_OBJECT, $parameters)
            && $parameters[RouteObjectInterface::ROUTE_OBJECT] instanceof PageRoute
        ) {
            $route = $parameters[RouteObjectInterface::ROUTE_OBJECT];
            unset($parameters[RouteObjectInterface::ROUTE_OBJECT]);
        } else {
            $route = $this->provider->getRouteByName($name);
        }

        $compiledRoute = $route->compile();

        if (
            $route instanceof PageRoute
            && 0 === \count(array_intersect_key(
                array_filter(array_merge($route->getDefaults(), $parameters)),
                array_flip($compiledRoute->getVariables())
            ))
        ) {
            $staticPrefix = $compiledRoute->getStaticPrefix();
            $indexPath = ($route->getUrlPrefix() ? '/'.$route->getUrlPrefix() : '').'/index';

            if ($staticPrefix === $indexPath || $staticPrefix === $indexPath.$route->getUrlSuffix()) {
                $route->setPath('/');
                $route->setUrlSuffix('');
                $compiledRoute = $route->compile();
            }
        }

        try {
            return $this->doGenerate(
                $compiledRoute->getVariables(),
                $route->getDefaults(),
                $route->getRequirements(),
                $compiledRoute->getTokens(),
                $parameters,
                $route->getDefault('_canonical_route') ?: $name,
                $referenceType,
                $compiledRoute->getHostTokens(),
                $route->getSchemes()
            );
        } catch (ExceptionInterface $exception) {
            throw new RouteParametersException($route, $parameters, $referenceType, $exception);
        }
    }
}
