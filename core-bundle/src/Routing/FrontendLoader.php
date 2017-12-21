<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FrontendLoader extends Loader
{
    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * @param bool $prependLocale
     */
    public function __construct(bool $prependLocale)
    {
        $this->prependLocale = $prependLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $routes = new RouteCollection();

        $defaults = [
            '_token_check' => true,
            '_controller' => 'ContaoCoreBundle:Frontend:index',
            '_scope' => ContaoCoreBundle::SCOPE_FRONTEND,
        ];

        $this->addFrontendRoute($routes, $defaults);
        $this->addIndexRoute($routes, $defaults);

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null): bool
    {
        return 'contao_frontend' === $type;
    }

    /**
     * Adds the frontend route, which is language-aware.
     *
     * @param RouteCollection $routes
     * @param array           $defaults
     */
    private function addFrontendRoute(RouteCollection $routes, array $defaults): void
    {
        $route = new Route('/{alias}%contao.url_suffix%', $defaults, ['alias' => '.+']);

        $this->addLocaleToRoute($route);

        $routes->add('contao_frontend', $route);
    }

    /**
     * Adds a route to redirect a user to the index page.
     *
     * @param RouteCollection $routes
     * @param array           $defaults
     */
    private function addIndexRoute(RouteCollection $routes, array $defaults): void
    {
        $route = new Route('/', $defaults);

        $this->addLocaleToRoute($route);

        $routes->add('contao_index', $route);
    }

    /**
     * Adds the locale to the route if prepend_locale is enabled.
     *
     * @param Route $route
     */
    private function addLocaleToRoute(Route $route): void
    {
        if ($this->prependLocale) {
            $route->setPath('/{_locale}'.$route->getPath());
            $route->addRequirements(['_locale' => '[a-z]{2}(\-[A-Z]{2})?']);
        } else {
            $route->addDefaults(['_locale' => null]);
        }
    }
}
