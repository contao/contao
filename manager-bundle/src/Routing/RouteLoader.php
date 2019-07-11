<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Routing;

use Contao\ManagerPlugin\PluginLoader;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader
{
    /**
     * @var LoaderInterface
     */
    private $loader;

    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var string
     */
    private $rootDir;

    public function __construct(LoaderInterface $loader, PluginLoader $pluginLoader, KernelInterface $kernel, string $rootDir)
    {
        $this->loader = $loader;
        $this->pluginLoader = $pluginLoader;
        $this->kernel = $kernel;
        $this->rootDir = $rootDir;
    }

    /**
     * Returns a route collection build from all plugin routes.
     */
    public function loadFromPlugins(): RouteCollection
    {
        $collection = array_reduce(
            $this->pluginLoader->getInstancesOf(PluginLoader::ROUTING_PLUGINS, true),
            function (RouteCollection $collection, RoutingPluginInterface $plugin): RouteCollection {
                $routes = $plugin->getRouteCollection($this->loader->getResolver(), $this->kernel);

                if ($routes instanceof RouteCollection) {
                    $collection->addCollection($routes);
                }

                return $collection;
            },
            new RouteCollection()
        );

        // Load the routing.yml file if it exists
        if ($configFile = $this->getConfigFile('routing.yml')) {
            $routes = $this->loader->getResolver()->resolve($configFile)->load($configFile);

            if ($routes instanceof RouteCollection) {
                $collection->addCollection($routes);
            }
        }

        // Make sure the Contao frontend routes are always loaded last
        foreach (['contao_frontend', 'contao_index', 'contao_root', 'contao_catch_all'] as $name) {
            if ($route = $collection->get($name)) {
                $collection->add($name, $route);
            }
        }

        return $collection;
    }

    private function getConfigFile(string $file): ?string
    {
        if (file_exists($this->rootDir.'/config/'.$file)) {
            return $this->rootDir.'/config/'.$file;
        }

        // Fallback to the legacy config file (see #566)
        if (file_exists($this->rootDir.'/app/config/'.$file)) {
            @trigger_error('Using the "app/config" folder has been deprecated and will no longer work in Contao 5.0. Use the "config" folder instead.', E_USER_DEPRECATED);

            return $this->rootDir.'/app/config/'.$file;
        }

        return null;
    }
}
