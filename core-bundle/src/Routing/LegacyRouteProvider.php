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

@trigger_error('The following routes are deprecated: contao_frontend, contao_index, contao_root, contao_catch_all', E_USER_DEPRECATED);

/**
 * @internal
 *
 * @deprecated
 */
class LegacyRouteProvider implements RouteProviderInterface
{
    /**
     * @var FrontendLoader
     */
    private $frontendLoader;

    public function __construct(FrontendLoader $frontendLoader)
    {
        $this->frontendLoader = $frontendLoader;
    }

    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        return new RouteCollection();
    }

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

        throw new RouteNotFoundException('No route for '.$name);
    }

    public function getRoutesByNames($names): array
    {
        return [];
    }
}
