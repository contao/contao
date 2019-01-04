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

use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteProvider implements RouteProviderInterface
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

    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        return $this->routeProvider->getRouteCollectionForRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name): Route
    {
        if ('contao_frontend' === $name || 'contao_index' === $name) {
            return $this->frontendLoader->load('.', 'contao_frontend')->get($name);
        }

        if ('contao_root' === $name) {
            return new Route(
                '/',
                [
                    '_scope' => 'frontend',
                    '_token_check' => true,
                    '_controller' => 'Contao\CoreBundle\Controller\FrontendController::indexAction',
                ]
            );
        }

        if ('contao_catch_all' === $name) {
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

        return $this->routeProvider->getRouteByName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names): array
    {
        return $this->routeProvider->getRoutesByNames($names);
    }
}
