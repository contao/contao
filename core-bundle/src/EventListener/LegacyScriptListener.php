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
 * Set the current route as TL_SCRIPT constant for legacy code.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class LegacyScriptListener
{
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
     * Set the TL_SCRIPT constant based on current request.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Define the TL_SCRIPT constant (backwards compatibility)
        if (!defined('TL_SCRIPT')) {
            $request = $event->getRequest();
            $route   = $request->attributes->get('_route');
            define('TL_SCRIPT', $this->router->generate($route, $request->attributes->all()));
        }
    }
}
