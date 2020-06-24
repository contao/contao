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
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Contao\CoreBundle\Routing\Content\PageProviderInterface;
use Contao\CoreBundle\Routing\Content\ContentRoute;

class ContentResolvingGenerator extends SymfonyUrlGenerator
{
    /**
     * @var array<ContentUrlResolverInterface>
     */
    private $resolvers;

    /**
     * @var ServiceLocator
     */
    private $pageProviders;

    public function __construct(iterable $resolvers, ServiceLocator $pageProviders, LoggerInterface $logger = null)
    {
        parent::__construct(new RouteCollection(), new RequestContext(), $logger);

        $this->resolvers = $resolvers;
        $this->pageProviders = $pageProviders;
    }

    /**
     * @param string $name
     * @param array  $parameters
     * @param int    $referenceType
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        if (ContentRoute::ROUTE_NAME === $name && isset($parameters[ContentRoute::CONTENT_PARAMETER])) {
            $route = $this->resolveContent($parameters[ContentRoute::CONTENT_PARAMETER]);
            unset($parameters[ContentRoute::CONTENT_PARAMETER]);
        } elseif (
            ContentRoute::ROUTE_NAME === $name
            && isset($parameters[ContentRoute::ROUTE_OBJECT_PARAMETER])
            && $parameters[ContentRoute::ROUTE_OBJECT_PARAMETER] instanceof Route
        ) {
            $route = $parameters[ContentRoute::ROUTE_OBJECT_PARAMETER];
            unset($parameters[ContentRoute::ROUTE_OBJECT_PARAMETER]);
        } else {
            throw new ContentRouteNotFoundException($name);
        }

        // the Route has a cache of its own and is not recompiled as long as it does not get modified
        $compiledRoute = $route->compile();
        $hostTokens = $compiledRoute->getHostTokens();

        $debug_message = ContentRouteNotFoundException::getRouteDebugMessage($name);

        return $this->doGenerate($compiledRoute->getVariables(), $route->getDefaults(), $route->getRequirements(), $compiledRoute->getTokens(), $parameters, $debug_message, $referenceType, $hostTokens);
    }

    private function resolveContent($content): Route
    {
        if ($content instanceof Route) {
            return $content;
        }

        $route = null;

        foreach ($this->resolvers as $resolver) {
            if ($resolver->supportsContent($content)) {
                $route = $resolver->resolveContent($content);
                $route->setDefault(ContentUrlResolverInterface::ATTRIBUTE, $resolver);
                break;
            }
        }

        if ($route instanceof ContentRoute) {
            $page = $route->getPage();

            if ($this->pageProviders->has($page->type)) {

                /** @var PageProviderInterface $pageProvider */
                $pageProvider = $this->pageProviders->get($page->type);

                $route = $pageProvider->getRouteForPage($route->getPage(), $route->getContent());
                $route->setDefault(PageProviderInterface::ATTRIBUTE, $pageProvider);
            }
        }

        if ($route instanceof Route) {
            return $route;
        }

        throw new ContentRouteNotFoundException($content);
    }
}
