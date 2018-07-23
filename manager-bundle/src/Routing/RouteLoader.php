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

    public function __construct(LoaderInterface $loader, PluginLoader $pluginLoader, KernelInterface $kernel)
    {
        $this->loader = $loader;
        $this->pluginLoader = $pluginLoader;
        $this->kernel = $kernel;
    }

    /**
     * Returns a route collection build from all plugin routes.
     */
    public function loadFromPlugins(): RouteCollection
    {
        /** @var RouteCollection $collection */
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

        $configFile = $this->kernel->getRootDir().'/config/routing.yml';

        // Load the app/config/routing.yml file if it exists
        if (file_exists($configFile) && $this->loader->supports($configFile)) {
            $this->loader->load($configFile);
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
