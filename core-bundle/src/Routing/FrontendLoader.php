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
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    private $defaultLocale;

    /**
     * @var bool
     */
    private $prependLocale;

    /**
     * Constructor.
     *
     * @param string $format        The URL suffix
     * @param string $defaultLocale The default locale
     * @param bool   $prependLocale Prepend the locale
     */
    public function __construct($format, $defaultLocale, $prependLocale)
    {
        $this->format        = isset($format[2]) ? substr($format, 1) : '';
        $this->defaultLocale = $defaultLocale;
        $this->prependLocale = $prependLocale;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null)
    {
        $pattern  = '/{alias}';
        $defaults = ['_controller' => 'ContaoCoreBundle:Frontend:index'];
        $require  = ['alias' => '.*'];

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

        $routes = new RouteCollection();
        $routes->add('contao_frontend', new Route($pattern, $defaults, $require));

        return $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'contao_frontend' === $type;
    }
}
