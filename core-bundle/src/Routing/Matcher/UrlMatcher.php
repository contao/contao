<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\Matcher;

use Contao\CoreBundle\Controller\RedirectController;
use Symfony\Cmf\Component\Routing\NestedMatcher\FinalMatcherInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class UrlMatcher extends RedirectableUrlMatcher implements FinalMatcherInterface
{
    /**
     * Initializes the object with an empty route collection and request context,
     * because both will be set in the finalMatch() method.
     */
    public function __construct()
    {
        parent::__construct(new RouteCollection(), new RequestContext());
    }

    public function finalMatch(RouteCollection $collection, Request $request): array
    {
        $this->routes = $collection;

        $context = new RequestContext();
        $context->fromRequest($request);

        $this->setContext($context);

        return $this->matchRequest($request);
    }

    public function redirect($path, $route, $scheme = null): array
    {
        return [
            '_controller' => RedirectController::class.'::urlRedirectAction',
            'path' => $path,
            'permanent' => true,
            'scheme' => $scheme,
            'httpPort' => $this->context->getHttpPort(),
            'httpsPort' => $this->context->getHttpsPort(),
            '_route' => $route,
        ];
    }

    protected function getAttributes(Route $route, string $name, array $attributes): array
    {
        $attributes[RouteObjectInterface::ROUTE_NAME] = $name;
        $attributes[RouteObjectInterface::ROUTE_OBJECT] = $route;

        return $this->mergeDefaults($attributes, $route->getDefaults());
    }
}
