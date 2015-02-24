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

// FIXME: add the phpDoc comments
class FrontendLoader extends Loader
{
    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        // FIXME: Make sure the config is only in parameters.yml
        //$addlang = Config::get('addLanguageToUrl');
        //$suffix  = substr(Config::get('urlSuffix'), 1);
        $addlang = true;
        $suffix = 'html';

        $routes = new RouteCollection();

        $defaults = [
            '_controller' => 'ContaoCoreBundle:Frontend:index'
        ];

        $pattern = '/{alias}';
        $require = ['alias' => '.*'];

        // URL suffix
        if ($suffix) {
            $pattern .= '.{_format}';

            $require['_format']  = $suffix;
            $defaults['_format'] = $suffix;
        }

        // Add language to URL
        if ($addlang) {
            $require['_locale'] = '[a-z]{2}(\-[A-Z]{2})?';

            $route = new Route('/{_locale}' . $pattern, $defaults, $require);
            $routes->add('contao_locale', $route);
        }

        // Default route
        $route = new Route($pattern, $defaults, $require);
        $routes->add('contao_default', $route);

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return true; // the loader of the integration bundle does not check for support
    }
}
