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
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class LegacyRouteProvider implements RouteProviderInterface
{
    private FrontendLoader $frontendLoader;

    /**
     * @internal
     */
    public function __construct(FrontendLoader $frontendLoader)
    {
        $this->frontendLoader = $frontendLoader;
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        return new RouteCollection();
    }

    public function getRoutesByNames($names): array
    {
        return [];
    }

    public function getRouteByName($name): Route
    {
        $route = $this->loadRoute((string) $name);

        trigger_deprecation('contao/core-bundle', '4.10', sprintf('The "%s" route has been deprecated and is only available in legacy routing mode.', $name));

        return $route;
    }

    private function loadRoute(string $name): Route
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

        throw new RouteNotFoundException('No route for '.$name);
    }
}
