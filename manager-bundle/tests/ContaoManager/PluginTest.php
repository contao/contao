<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\ContaoManager;

use Contao\ManagerBundle\ContaoManager\Plugin;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PluginTest extends ContaoTestCase
{
    public function testInstantiation(): void
    {
        $plugin = new Plugin();

        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Plugin', $plugin);
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\BundlePluginInterface', $plugin);
        $this->assertInstanceOf('Contao\ManagerPlugin\Config\ConfigPluginInterface', $plugin);
        $this->assertInstanceOf('Contao\ManagerPlugin\Routing\RoutingPluginInterface', $plugin);
        $this->assertTrue(method_exists($plugin, 'autoloadModules'));
    }

    public function testGetBundles(): void
    {
        $fs = new Filesystem();
        $fs->mkdir([$this->getTempDir().'/foo1', $this->getTempDir().'/foo2', $this->getTempDir().'/foo3']);
        $fs->touch($this->getTempDir().'/foo3/.skip');

        Plugin::autoloadModules($this->getTempDir());

        $parser = $this->createMock(ParserInterface::class);

        $parser
            ->expects($this->atLeastOnce())
            ->method('parse')
            ->willReturnCallback(
                function ($resource): array {
                    return [$resource];
                }
            )
        ;

        $plugin = new Plugin();
        $configs = $plugin->getBundles($parser);

        $this->assertCount(18, $configs);
        $this->assertContains('foo1', $configs);
        $this->assertContains('foo2', $configs);
        $this->assertNotContains('foo3', $configs);
    }

    public function testRegisterContainerConfigurationInProd(): void
    {
        $files = [];
        $loader = $this->createMock(LoaderInterface::class);

        $loader
            ->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(
                function ($resource) use (&$files): void {
                    if (\is_string($resource)) {
                        $files[] = basename($resource);
                    } elseif (\is_callable($resource)) {
                        $container = new ContainerBuilder();
                        $container->setParameter('kernel.environment', 'prod');

                        $resource($container);
                    }
                }
            )
        ;

        $plugin = new Plugin();
        $plugin->registerContainerConfiguration($loader, []);

        $this->assertContains('config_prod.yml', $files);
    }

    public function testRegisterContainerConfigurationInDev(): void
    {
        $files = [];
        $loader = $this->createMock(LoaderInterface::class);

        $loader
            ->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(
                function ($resource) use (&$files): void {
                    if (\is_string($resource)) {
                        $files[] = basename($resource);
                    } elseif (\is_callable($resource)) {
                        $container = new ContainerBuilder();
                        $container->setParameter('kernel.environment', 'dev');

                        $resource($container);
                    }
                }
            )
        ;

        $plugin = new Plugin();
        $plugin->registerContainerConfiguration($loader, []);

        $this->assertContains('config_dev.yml', $files);
    }

    public function testGetRouteCollectionInProd(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('prod')
        ;

        $plugin = new Plugin();
        $resolver = $this->createMock(LoaderResolverInterface::class);

        $this->assertNull($plugin->getRouteCollection($resolver, $kernel));
    }

    public function testGetRouteCollectionInDev(): void
    {
        $loader = $this->createMock(LoaderInterface::class);

        $loader
            ->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(
                function (string $file): RouteCollection {
                    $collection = new RouteCollection();
                    $collection->add(basename($file).'_foobar', new Route('/foobar'));

                    return $collection;
                }
            )
        ;

        $resolver = $this->createMock(LoaderResolverInterface::class);

        $resolver
            ->expects($this->atLeastOnce())
            ->method('resolve')
            ->willReturn($loader)
        ;

        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('dev')
        ;

        $plugin = new Plugin();
        $collection = $plugin->getRouteCollection($resolver, $kernel);

        /** @var Route[] $routes */
        $routes = array_values($collection->all());

        $this->assertCount(3, $routes);
        $this->assertSame('/_wdt/foobar', $routes[0]->getPath());
        $this->assertSame('/_profiler/foobar', $routes[1]->getPath());
    }
}
