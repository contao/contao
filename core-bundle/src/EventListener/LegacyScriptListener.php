<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Sets the current route as TL_SCRIPT constant (backwards compatibility).
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class LegacyScriptListener
{
    /**
     * @var Router
     */
    private $router;

    /**
     * Constructor.
     *
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Sets the TL_SCRIPT constant based on current request.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (defined('TL_SCRIPT')) {
            return;
        }

        $request = $event->getRequest();
        $route   = $request->attributes->get('_route');
        $params  = $request->attributes->get('_route_params');

        define('TL_SCRIPT', $this->router->generate($route, $params));
    }
}
