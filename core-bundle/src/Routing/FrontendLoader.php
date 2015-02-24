<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Routing;

use Contao\Config;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class FrontendLoader extends Loader
{
    public function load($resource, $type = null)
    {
        $pattern = '/{alias}';

        $defaults = array(
            '_controller' => 'ContaoCoreBundle:Frontend:index',
        );

        $requirements = array(
            'alias' => '.*',
        );

        if ($GLOBALS['TL_CONFIG']['urlSuffix'] != '') {
            $pattern .= '.{_format}';
            $requirements['_format'] = substr(Config::get('urlSuffix'), 1);
            $defaults['_format'] = substr(Config::get('urlSuffix'), 1);
        }

        if (Config::get('addLanguageToUrl')) {
            $pattern = '/{_locale}' . $pattern;
        }

        $routes = new RouteCollection();
        $route = new Route($pattern, $defaults, $requirements);
        $routes->add('contao_frontend', $route);

        return $routes;
    }

    /**
     * The BundleLoader of the integration bundle does not check for support.
     *
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return true;
    }
}
