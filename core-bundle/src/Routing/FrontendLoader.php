<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Routing;

use Contao\CoreBundle\ContaoCoreBundle;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds routes for the Contao front end.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FrontendLoader extends Loader
{
    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * Constructor.
     *
     * @param bool $prependLocale Prepend the locale
     */
    public function __construct($prependLocale)
    {
        $this->prependLocale = $prependLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();

        $defaults = [
            '_controller' => 'ContaoCoreBundle:Frontend:index',
            '_scope'      => ContaoCoreBundle::SCOPE_FRONTEND,
        ];

        $this->addFrontendRoute($routes, $defaults);
        $this->addIndexRoute($routes, $defaults);

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'contao_frontend' === $type;
    }

    /**
     * Adds the frontend route, which is language-aware.
     *
     * @param RouteCollection $routes   A collection of routes
     * @param array           $defaults Default parameters for the route
     */
    private function addFrontendRoute(RouteCollection $routes, array $defaults)
    {
        $route = new Route('/{alias}%contao.url_suffix%', $defaults, ['alias' => '.+']);

        $this->addLocaleToRoute($route);

        $routes->add('contao_frontend', $route);
    }

    /**
     * Adds a route to redirect a user to the index page.
     *
     * @param RouteCollection $routes   A collection of routes
     * @param array           $defaults Default parameters for the route
     */
    private function addIndexRoute(RouteCollection $routes, array $defaults)
    {
        $route = new Route('/', $defaults);

        $this->addLocaleToRoute($route);

        $routes->add('contao_index', $route);
    }

    /**
     * Adds the locale to the route if prepend_locale is enabled.
     *
     * @param Route $route The route
     */
    private function addLocaleToRoute(Route $route)
    {
        if ($this->prependLocale) {
            $route->setPath('/{_locale}' . $route->getPath());
            $route->addRequirements(['_locale' => '[a-z]{2}(\-[A-Z]{2})?']);
        } else {
            $route->addDefaults(['_locale' => null]);
        }
    }
}
