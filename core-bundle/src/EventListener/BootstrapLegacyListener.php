<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Bootstraps the legacy Contao environment
 *
 * @author Christian Schiffler <https://github.com/discordier>
 * @author Yanick Witschi <https://github.com/toflar>
 */
class BootstrapLegacyListener
{
    /**
     * The router service to retrieve routes from.
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * The kernel root directory.
     *
     * @var string
     */
    private $rootDir;

    /**
     * Create a new instance.
     *
     * @param RouterInterface $router  The router service to retrieve routes from.
     * @param string          $rootDir The kernel root directory.
     */
    public function __construct(RouterInterface $router, $rootDir)
    {
        $this->router  = $router;
        $this->rootDir = $rootDir;
    }

    /**
     * Boot the legacy code for all web SAPIs.
     *
     * @param GetResponseEvent $event The event object
     *
     * @throws RouteNotFoundException When no route could be found for the request.
     */
    public function onBootLegacyForRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->attributes->has('_route')) {
            throw new RouteNotFoundException(
                'No valid route found for the request - can not boot Contao legacy environment.'
            );
        }

        $routeName = $request->attributes->get('_route');
        $mode      = 'FE';
        if ($request->attributes->has('_scope')
            && 'backend' === $request->attributes->get('_scope')
        ) {
            $mode = 'BE';
        }

        $this->bootLegacy(
            $mode,
            $this->router->generate($routeName, $request->attributes->get('_route_params'))
        );
    }

    /**
     * Boot the legacy code for SAPI == cli.
     */
    public function onBootLegacyForConsole()
    {
        $this->bootLegacy('FE', 'console');
    }

    /**
     * The real booting of the legacy code happens in here.
     *
     * @param string $mode   The mode we are running in (BE or FE).
     * @param string $script The entry script we are being called from.
     */
    private function bootLegacy($mode, $script)
    {
        // We define these constants here for reasons of backwards compatibility only.
        // They will be removed in Contao 5 and should not be used anymore.
        define('TL_MODE', $mode);
        define('TL_SCRIPT', $script);
        define('TL_ROOT', dirname($this->rootDir));

        // FIXME: Get rid of the bootstrap.php
        require_once __DIR__ . '/../../contao/bootstrap.php';
    }
}
