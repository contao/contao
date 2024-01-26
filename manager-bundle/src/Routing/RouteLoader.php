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
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader implements RouteLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly PluginLoader $pluginLoader,
        private readonly KernelInterface $kernel,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Returns a route collection build from all plugin routes.
     */
    public function loadFromPlugins(): RouteCollection
    {
        $collection = new RouteCollection();

        // Load the routing.yaml file first if it exists, so it takes
        // precedence over all other routes (see #2718)
        if ($configFile = $this->getConfigFile()) {
            $routes = $this->loader->getResolver()->resolve($configFile)->load($configFile);

            if ($routes instanceof RouteCollection) {
                $collection->addCollection($routes);
            }
        } elseif (is_dir($path = Path::join($this->projectDir, 'src/Controller'))) {
            $routes = $this->loader->getResolver()->resolve($path)->load($path);

            if ($routes instanceof RouteCollection) {
                $collection->addCollection($routes);
            }
        }

        $collection = array_reduce(
            $this->pluginLoader->getInstancesOf(PluginLoader::ROUTING_PLUGINS, true),
            function (RouteCollection $collection, RoutingPluginInterface $plugin): RouteCollection {
                $routes = $plugin->getRouteCollection($this->loader->getResolver(), $this->kernel);

                if ($routes instanceof RouteCollection) {
                    $collection->addCollection($routes);
                }

                return $collection;
            },
            $collection,
        );

        // Make sure the Contao frontend routes are always loaded last
        foreach (['contao_frontend', 'contao_index', 'contao_root', 'contao_catch_all'] as $name) {
            if ($route = $collection->get($name)) {
                $collection->add($name, $route);
            }
        }

        return $collection;
    }

    private function getConfigFile(): string|null
    {
        if (file_exists($path = Path::join($this->projectDir, 'config/routes.yaml'))) {
            return $path;
        }

        if (file_exists($path = Path::join($this->projectDir, 'config/routes.yml'))) {
            trigger_deprecation('contao/manager-bundle', '5.0', 'Using a routes.yml file has been deprecated and will no longer work in Contao 6. Use a routes.yaml file instead.');

            return $path;
        }

        return null;
    }
}
