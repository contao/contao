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

use Contao\CoreBundle\Routing\Content\ContentResolverInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @internal
 */
class LegacyRouteProvider implements ContentResolverInterface, RouteProviderInterface
{
    /**
     * @var FrontendLoader
     */
    private $frontendLoader;

    /**
     * @var RouteProviderInterface
     */
    private $routeProvider;

    public function __construct(FrontendLoader $frontendLoader, RouteProviderInterface $routeProvider)
    {
        $this->frontendLoader = $frontendLoader;
        $this->routeProvider = $routeProvider;
    }

    public function supportsContent($content): bool
    {
        $routes = ['contao_frontend', 'contao_index', 'contao_root', 'contao_catch_all'];

        return \is_string($content) && \in_array($content, $routes);
    }

    public function resolveContent($content): Route
    {
        if ('contao_frontend' === $content || 'contao_index' === $content) {
            return $this->frontendLoader->load('.', 'contao_frontend')->get($content);
        }

        if ('contao_root' === $content) {
            return new Route(
                '/',
                [
                    '_scope' => 'frontend',
                    '_token_check' => true,
                    '_controller' => 'Contao\CoreBundle\Controller\FrontendController::indexAction',
                ]
            );
        }

        if ('contao_catch_all' === $content) {
            return new Route(
                '/{_url_fragment}',
                [
                    '_scope' => 'frontend',
                    '_token_check' => true,
                    '_controller' => 'Contao\CoreBundle\Controller\FrontendController::indexAction',
                ],
                ['_url_fragment' => '.*']
            );
        }

        throw new RouteNotFoundException('No route for '.$content);
    }


    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        @trigger_error(__METHOD__.' has been deprecated in Contao 4.10', E_USER_DEPRECATED);

        return $this->routeProvider->getRouteCollectionForRequest($request);
    }

    public function getRouteByName($name): Route
    {
        @trigger_error(__METHOD__.' has been deprecated in Contao 4.10', E_USER_DEPRECATED);

        try {
            return $this->resolveContent($name);
        } catch (RouteNotFoundException $e) {
            return $this->routeProvider->getRouteByName($name);
        }
    }

    public function getRoutesByNames($names): array
    {
        @trigger_error(__METHOD__.' has been deprecated in Contao 4.10', E_USER_DEPRECATED);

        return $this->routeProvider->getRoutesByNames($names);
    }
}
