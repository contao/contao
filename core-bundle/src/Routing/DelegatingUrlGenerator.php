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

use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Contao\CoreBundle\Routing\Content\PageProviderInterface;
use Contao\CoreBundle\Routing\Content\PageRoute;

class DelegatingUrlGenerator extends SymfonyUrlGenerator implements VersatileGeneratorInterface
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
     * @param array $parameters
     * @param int   $referenceType
     */
    public function generate($content, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        $route = $this->resolveContent($content);

        // the Route has a cache of its own and is not recompiled as long as it does not get modified
        $compiledRoute = $route->compile();
        $hostTokens = $compiledRoute->getHostTokens();

        $debug_message = $this->getRouteDebugMessage($content);

        return $this->doGenerate($compiledRoute->getVariables(), $route->getDefaults(), $route->getRequirements(), $compiledRoute->getTokens(), $parameters, $debug_message, $referenceType, $hostTokens);
    }

    public function supports($content): bool
    {
        if ($content instanceof Route) {
            return true;
        }

        foreach ($this->resolvers as $provider) {
            if ($provider->supportsContent($content)) {
                return true;
            }
        }

        return false;
    }

    public function getRouteDebugMessage($name, array $parameters = []): string
    {
        if (is_scalar($name)) {
            return $name;
        }

        if (\is_array($name)) {
            return serialize($name);
        }

        if ($name instanceof RouteObjectInterface) {
            return 'key '.$name->getRouteKey();
        }

        if ($name instanceof Route) {
            return 'path '.$name->getPath();
        }

        if (\is_object($name)) {
            return \get_class($name);
        }

        return 'null route';
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

                if ($route instanceof Route) {
                    $route->setDefault(ContentUrlResolverInterface::ATTRIBUTE, $resolver);
                }
                break;
            }
        }

        if ($route instanceof PageRoute) {
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

        if (null !== $route && $route !== $content) {
            return $this->resolveContent($route);
        }

        throw new RouteNotFoundException('No route found for '.$this->getRouteDebugMessage($content));
    }
}
