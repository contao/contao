<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Route;
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
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router, $rootDir)
    {
        $this->router = $router;
        $this->rootDir = $rootDir;
    }

    /**
     * @param GetResponseEvent $event The event object
     */
    public function onBootLegacyForRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $routeName = $request->attributes->get('_route');

        /** @var Route $route */
        $route = $this->router->getRouteCollection()->get($routeName);

        $mode = 'FE';

        if ($route->getDefault('_scope')
            && 'backend' === $route->getDefault('_scope')) {
            $mode = 'BE';
        }

        $this->bootLegacy(
            $mode,
            $this->router->generate($route, $request->attributes->all()),
            dirname($this->rootDir)
        );
    }

    /**
     * @param GetResponseEvent $event The event object
     */
    public function onBootLegacyForConsole(ConsoleCommandEvent $event)
    {
        $this->bootLegacy('FE', 'console', dirname($this->rootDir));
    }


    private function bootLegacy($mode, $script, $root)
    {
        if (!defined('TL_MODE')) {
            define('TL_MODE', $mode);
        }

        if (!defined('TL_START')) {
            define('TL_START', microtime(true));
        }

        if (!defined('TL_REFERER_ID')) {
            define('TL_REFERER_ID', substr(md5(TL_START), 0, 8));
        }

        if (!defined('TL_SCRIPT')) {
            define('TL_SCRIPT', $script);
        }

        if (!defined('TL_ROOT')) {
            define('TL_ROOT', $root);
        }

        // FIXME: Get rid of the bootstrap.php
        require_once __DIR__ . '/../../contao/bootstrap.php';
    }
}
