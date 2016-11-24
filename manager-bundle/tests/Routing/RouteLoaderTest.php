<?php


namespace Contao\ManagerBundle\Test\HttpKernel;

use Contao\ManagerBundle\ContaoManager\PluginLoader;
use Contao\ManagerBundle\ContaoManager\Routing\RoutingPluginInterface;
use Contao\ManagerBundle\Routing\RouteLoader;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the RouteLoader class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class RouteLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $loader = $this->getMock(LoaderInterface::class);
        $kernel = $this->getMock(KernelInterface::class);

        $pluginLoader = $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $routeLoader = new RouteLoader(
            $loader,
            $pluginLoader,
            $kernel
        );

        $this->assertInstanceOf('Contao\ManagerBundle\Routing\RouteLoader', $routeLoader);
    }

    public function testLoadFromPlugins()
    {
        $loaderResolver = $this->getMock(LoaderResolverInterface::class);
        $loader = $this->getMock(LoaderInterface::class);

        $loader
            ->expects($this->exactly(2))
            ->method('getResolver')
            ->willReturn($loaderResolver)
        ;

        $kernel = $this->getMock(KernelInterface::class);

        $plugin1 = $this->mockRoutePlugin('foo', '/foo/path');
        $plugin2 = $this->mockRoutePlugin('foo2', '/foo2/path2');

        $pluginLoader = $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pluginLoader
            ->expects($this->once())
            ->method('getInstancesOf')
            ->with(PluginLoader::ROUTING_PLUGINS, true)
            ->willReturn([$plugin1, $plugin2])
        ;

        $routeLoader = new RouteLoader(
            $loader,
            $pluginLoader,
            $kernel
        );

        $collection = $routeLoader->loadFromPlugins();

        $this->assertCount(2, $collection);
        $this->assertNotNull($collection->get('foo'));
        $this->assertNotNull($collection->get('foo2'));
        $this->assertInstanceOf(Route::class, $collection->get('foo'));
        $this->assertInstanceOf(Route::class, $collection->get('foo2'));
    }

    private function mockRoutePlugin($routeName, $routePath)
    {
        $collection = new RouteCollection();
        $collection->add($routeName, new Route($routePath));

        $plugin = $this->getMock(RoutingPluginInterface::class);

        $plugin
            ->expects($this->atLeastOnce())
            ->method('getRouteCollection')
            ->willReturn($collection)
        ;

        return $plugin;
    }
}
