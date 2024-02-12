<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Routing;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerBundle\Routing\RouteLoader;
use Contao\ManagerPlugin\PluginLoader;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Loader\AttributeDirectoryLoader;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoaderTest extends ContaoTestCase
{
    public function testLoadsRoutesYaml(): void
    {
        $loader = $this->createMock(YamlFileLoader::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with(Path::join(__DIR__, '../Fixtures/Routing/WithRoutingYaml/config/routes.yaml'))
        ;

        $loaderResolver = $this->createMock(LoaderResolverInterface::class);
        $loaderResolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn($loader)
        ;

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->exactly(1))
            ->method('getResolver')
            ->willReturn($loaderResolver)
        ;

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(PluginLoader::ROUTING_PLUGINS, true)
            ->willReturn([])
        ;

        $routeLoader = new RouteLoader(
            $loader,
            $pluginLoader,
            $this->createMock(ContaoKernel::class),
            __DIR__.'/../Fixtures/Routing/WithRoutingYaml',
        );

        $routeLoader->loadFromPlugins();
    }

    public function testLoadsAppController(): void
    {
        $loader = $this->createMock(AttributeDirectoryLoader::class);
        $loader
            ->expects($this->once())
            ->method('load')
            ->with(Path::join(__DIR__, '../Fixtures/Routing/WithAppController/src/Controller'))
        ;

        $loaderResolver = $this->createMock(LoaderResolverInterface::class);
        $loaderResolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn($loader)
        ;

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->exactly(1))
            ->method('getResolver')
            ->willReturn($loaderResolver)
        ;

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(PluginLoader::ROUTING_PLUGINS, true)
            ->willReturn([])
        ;

        $routeLoader = new RouteLoader(
            $loader,
            $pluginLoader,
            $this->createMock(ContaoKernel::class),
            __DIR__.'/../Fixtures/Routing/WithAppController',
        );

        $routeLoader->loadFromPlugins();
    }

    public function testLoadFromPlugins(): void
    {
        $loaderResolver = $this->createMock(LoaderResolverInterface::class);

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->exactly(2))
            ->method('getResolver')
            ->willReturn($loaderResolver)
        ;

        $plugin1 = $this->mockRoutePlugin('foo', '/foo/path');
        $plugin2 = $this->mockRoutePlugin('foo2', '/foo2/path2');

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(PluginLoader::ROUTING_PLUGINS, true)
            ->willReturn([$plugin1, $plugin2])
        ;

        $routeLoader = new RouteLoader(
            $loader,
            $pluginLoader,
            $this->createMock(ContaoKernel::class),
            $this->getTempDir(),
        );

        $collection = $routeLoader->loadFromPlugins();

        $this->assertCount(2, $collection);
        $this->assertNotNull($collection->get('foo'));
        $this->assertNotNull($collection->get('foo2'));
        $this->assertInstanceOf(Route::class, $collection->get('foo'));
        $this->assertInstanceOf(Route::class, $collection->get('foo2'));
    }

    public function testCatchAllIsLast(): void
    {
        $loaderResolver = $this->createMock(LoaderResolverInterface::class);

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->exactly(4))
            ->method('getResolver')
            ->willReturn($loaderResolver)
        ;

        $plugin1 = $this->mockRoutePlugin('foo', '/foo/path');
        $plugin2 = $this->mockRoutePlugin('contao_catch_all', '/foo2/path2');
        $plugin3 = $this->mockRoutePlugin('foo3', '/foo3/path3');
        $plugin4 = $this->mockRoutePlugin('foo4', '/foo4/path4');

        $pluginLoader = $this->createMock(PluginLoader::class);
        $pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(PluginLoader::ROUTING_PLUGINS, true)
            ->willReturn([$plugin1, $plugin2, $plugin3, $plugin4])
        ;

        $routeLoader = new RouteLoader(
            $loader,
            $pluginLoader,
            $this->createMock(ContaoKernel::class),
            $this->getTempDir(),
        );

        $routes = $routeLoader->loadFromPlugins()->all();

        $this->assertCount(4, $routes);
        $this->assertArrayHasKey('contao_catch_all', $routes);
        $this->assertSame(3, array_search('contao_catch_all', array_keys($routes), true));
    }

    private function mockRoutePlugin(string $routeName, string $routePath): RoutingPluginInterface&MockObject
    {
        $collection = new RouteCollection();
        $collection->add($routeName, new Route($routePath));

        $plugin = $this->createMock(RoutingPluginInterface::class);
        $plugin
            ->expects($this->atLeastOnce())
            ->method('getRouteCollection')
            ->willReturn($collection)
        ;

        return $plugin;
    }
}
