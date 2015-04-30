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
     * @var string
     */
    private $format;

    /**
     * @var string
     */
    private $defaultLocale = 'en';

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * Constructor.
     *
     * @param string $format        The URL suffix
     * @param bool   $prependLocale Prepend the locale
     */
    public function __construct($format, $prependLocale)
    {
        $this->format        = isset($format[2]) ? substr($format, 1) : '';
        $this->prependLocale = $prependLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $routes   = new RouteCollection();

        $defaults = [
            '_controller' => 'ContaoCoreBundle:Frontend:index',
            '_scope'      => ContaoCoreBundle::SCOPE_FRONTEND,
        ];

        $this->addFrontendRoute($routes, $defaults);
        $this->addRootRoute($routes, $defaults);
        $this->addCatchAllRoute($routes, $defaults);

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
        $pattern = '/{alias}';
        $require = ['alias' => '.*'];

        // URL suffix
        if ($this->format) {
            $pattern .= '.{_format}';

            $defaults['_format'] = $this->format;
            $require['_format']  = $this->format;
        }

        // Add language to URL
        if ($this->prependLocale) {
            $pattern = '/{_locale}' . $pattern;

            $require['_locale'] = '[a-z]{2}(\-[A-Z]{2})?';
        } else {
            $defaults['_locale'] = $this->defaultLocale;
        }

        $routes->add('contao_frontend', new Route($pattern, $defaults, $require));
    }

    /**
     * Adds a route to redirect a user to the empty domain.
     *
     * @param RouteCollection $routes   A collection of routes
     * @param array           $defaults Default parameters for the route
     */
    private function addRootRoute(RouteCollection $routes, array $defaults)
    {
        $routes->add('contao_root', new Route('/', $defaults));
    }

    /**
     * Adds a catch-all route to redirect all request to the Contao front end controller.
     *
     * @param RouteCollection $routes   A collection of routes
     * @param array           $defaults Default parameters for the route
     */
    private function addCatchAllRoute(RouteCollection $routes, array $defaults)
    {
        $defaults['_url_fragment'] = '';

        $routes->add('contao_catch_all', new Route('/{_url_fragment}', $defaults, ['_url_fragment' => '.*']));
    }
}
