<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

// FIXME: add the phpDoc comments
class FrontendLoader extends Loader
{
    /**
     * Flag determining if the urls shall be prepended with the locale.
     *
     * @var bool
     */
    private $prependLocale;

    /**
     * The format parameter value to use.
     *
     * @var string
     */
    private $format;

    /**
     * Create a new instance.
     *
     * @param bool   $prependLocale Flag determining if the urls shall be prepended with the locale.
     * @param string $format        The format parameter value to use.
     */
    public function __construct($prependLocale, $format)
    {
        $this->prependLocale = $prependLocale;
        $this->format        = isset($format[2]) ? substr($format, 1) : '';
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();

        $defaults = [
            '_controller' => 'ContaoCoreBundle:Frontend:index'
        ];

        $pattern = '/{alias}';
        $require = ['alias' => '.*'];

        // URL suffix
        if ($this->format) {
            $pattern .= '.{_format}';

            $require['_format']  = $this->format;
            $defaults['_format'] = $this->format;
        }

        // Add language to URL
        if ($this->prependLocale) {
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
