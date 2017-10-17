<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests\ContaoManager;

use Contao\ManagerBundle\ContaoManager\Plugin;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PluginTest extends TestCase
{
    /**
     * @var Plugin
     */
    private $plugin;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->plugin = new Plugin();
    }

    public function testInstantiation(): void
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Plugin', $this->plugin);
        $this->assertTrue(method_exists($this->plugin, 'autoloadModules'));
    }

    public function testGetBundles(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\BundlePluginInterface', $this->plugin);

        $tmpdir = sys_get_temp_dir().'/'.uniqid('PluginTest_', false);

        $fs = new Filesystem();
        $fs->mkdir([$tmpdir.'/foo1', $tmpdir.'/foo2', $tmpdir.'/foo3']);
        $fs->touch($tmpdir.'/foo3/.skip');

        Plugin::autoloadModules($tmpdir);

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

        $configs = $this->plugin->getBundles($parser);

        $this->assertCount(18, $configs);
        $this->assertContains('foo1', $configs);
        $this->assertContains('foo2', $configs);
        $this->assertNotContains('foo3', $configs);

        $fs->remove($tmpdir);
    }

    public function testRegisterContainerConfigurationInProd(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Config\ConfigPluginInterface', $this->plugin);

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
                        \call_user_func($resource, $container);
                    }
                }
            )
        ;

        $this->plugin->registerContainerConfiguration($loader, []);

        $this->assertContains('config_prod.yml', $files);
    }

    public function testRegisterContainerConfigurationInDev(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Config\ConfigPluginInterface', $this->plugin);

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
                        \call_user_func($resource, $container);
                    }
                }
            )
        ;

        $this->plugin->registerContainerConfiguration($loader, []);

        $this->assertContains('config_dev.yml', $files);
    }

    public function testGetRouteCollectionInProd(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Routing\RoutingPluginInterface', $this->plugin);

        $resolver = $this->createMock(LoaderResolverInterface::class);
        $kernel = $this->createMock(KernelInterface::class);

        $kernel
            ->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('prod')
        ;

        $this->assertNull($this->plugin->getRouteCollection($resolver, $kernel));
    }

    public function testGetRouteCollectionInDev(): void
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Routing\RoutingPluginInterface', $this->plugin);

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

        /** @var Route[] $routes */
        $routes = array_values($this->plugin->getRouteCollection($resolver, $kernel)->all());

        $this->assertCount(3, $routes);
        $this->assertSame('/_wdt/foobar', $routes[0]->getPath());
        $this->assertSame('/_profiler/foobar', $routes[1]->getPath());
    }
}
