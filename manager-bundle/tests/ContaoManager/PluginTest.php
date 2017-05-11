<?php

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
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests the Plugin class.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class PluginTest extends \PHPUnit_Framework_TestCase
{
    private $plugin;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->plugin = new Plugin();
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\ContaoManager\Plugin', $this->plugin);
        $this->assertTrue(method_exists($this->plugin, 'autoloadModules'));
    }

    public function testGetBundles()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Bundle\BundlePluginInterface', $this->plugin);

        $fs = new Filesystem();
        $tmpdir = sys_get_temp_dir().'/'.uniqid('PluginTest_', false);

        $fs->mkdir([$tmpdir.'/foo1', $tmpdir.'/foo2', $tmpdir.'/foo3']);
        touch($tmpdir.'/foo3/.skip');

        Plugin::autoloadModules($tmpdir);

        $parser = $this->getMock(ParserInterface::class);
        $parser
            ->expects($this->atLeastOnce())
            ->method('parse')
            ->willReturnCallback(
                function ($resource) {
                    return [$resource];
                }
            )
        ;

        $configs = $this->plugin->getBundles($parser);

        $this->assertCount(3, $configs);
        $this->assertContains('foo1', $configs);
        $this->assertContains('foo2', $configs);
        $this->assertNotContains('foo3', $configs);

        $fs->remove($tmpdir);
    }

    public function testRegisterContainerConfiguration()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Config\ConfigPluginInterface', $this->plugin);

        $files = [];

        $loader = $this->getMock(LoaderInterface::class);
        $loader
            ->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(
                function ($resource) use (&$files) {
                    if (is_string($resource)) {
                        $files[] = basename($resource);
                    } elseif (is_callable($resource)) {
                        $container = new ContainerBuilder();
                        $container->setParameter('kernel.environment', 'dev');
                        call_user_func($resource, $container);
                    }
                }
            )
        ;

        $this->plugin->registerContainerConfiguration($loader, []);

        $this->assertContains('framework.yml', $files);
        $this->assertContains('security.yml', $files);
        $this->assertContains('contao.yml', $files);
        $this->assertContains('twig.yml', $files);
        $this->assertContains('doctrine.yml', $files);
        $this->assertContains('swiftmailer.yml', $files);
        $this->assertContains('monolog.yml', $files);
        $this->assertContains('lexik_maintenance.yml', $files);
    }

    public function testGetRouteCollectionInProd()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Routing\RoutingPluginInterface', $this->plugin);

        $resolver = $this->getMock(LoaderResolverInterface::class);
        $kernel = $this->getMock(KernelInterface::class);

        $kernel
            ->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('prod')
        ;

        $this->assertNull($this->plugin->getRouteCollection($resolver, $kernel));
    }

    public function testGetRouteCollectionInDev()
    {
        $this->assertInstanceOf('Contao\ManagerPlugin\Routing\RoutingPluginInterface', $this->plugin);

        $resolver = $this->getMock(LoaderResolverInterface::class);
        $loader = $this->getMock(LoaderInterface::class);
        $kernel = $this->getMock(KernelInterface::class);

        $kernel
            ->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('dev')
        ;

        $resolver
            ->expects($this->atLeastOnce())
            ->method('resolve')
            ->willReturn($loader)
        ;

        $loader
            ->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(
                function ($file) {
                    $collection = new RouteCollection();
                    $collection->add(basename($file).'_foobar', new Route('/foobar'));

                    return $collection;
                }
            )
        ;

        /** @var Route[] $routes */
        $routes = array_values($this->plugin->getRouteCollection($resolver, $kernel)->all());

        $this->assertCount(3, $routes);
        $this->assertSame('/_wdt/foobar', $routes[0]->getPath());
        $this->assertSame('/_profiler/foobar', $routes[1]->getPath());
    }
}
