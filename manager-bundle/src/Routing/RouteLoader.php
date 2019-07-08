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

        // Load the app/config/routing.yml file if it exists
        if (file_exists($configFile = $this->rootDir.'/app/config/routing.yml')) {
            @trigger_error('Placing a routing.yml in /app/config is deprecated since Contao 4.8. Place it in the root /config folder instead.', E_USER_DEPRECATED);
            $routes = $this->loader->getResolver()->resolve($configFile)->load($configFile);

            if ($routes instanceof RouteCollection) {
                $collection->addCollection($routes);
            }
        }

        // Load the config/routing.yml file if it exists
        if (file_exists($configFile = $this->rootDir.'/config/routing.yml')) {
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
}
