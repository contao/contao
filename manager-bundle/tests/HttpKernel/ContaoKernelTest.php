<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the ContaoKernel class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoKernelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContaoKernel
     */
    private $kernel;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        /** @var PluginLoader|\PHPUnit_Framework_MockObject_MockObject $pluginLoader */
        $pluginLoader = $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pluginLoader
            ->expects($this->any())
            ->method('getInstancesOf')
            ->willReturn([])
        ;

        $this->kernel = new ContaoKernel('test', true);
        $this->kernel->setPluginLoader($pluginLoader);
    }

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoKernel', $this->kernel);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Kernel', $this->kernel);
    }

    /**
     * Tests the registerBundles() method.
     */
    public function testRegisterBundles()
    {
        /** @var BundleLoader|\PHPUnit_Framework_MockObject_MockObject $bundleLoader */
        $bundleLoader = $this->getMockBuilder(BundleLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $bundleLoader
            ->expects($this->once())
            ->method('getBundleConfigs')
            ->willReturn(
                [
                    new BundleConfig(ContaoManagerBundle::class),
                ]
            )
        ;

        $this->kernel->setBundleLoader($bundleLoader);

        $bundles = $this->kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayNotHasKey('AppBundle\\AppBundle', $bundles);
    }

    /**
     * Tests the registerBundles() method autoloads AppBundle.
     *
     * @runInSeparateProcess
     */
    public function testRegistersAppBundle()
    {
        /** @var BundleLoader|\PHPUnit_Framework_MockObject_MockObject $bundleLoader */
        $bundleLoader = $this->getMockBuilder(BundleLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $bundleLoader
            ->expects($this->once())
            ->method('getBundleConfigs')
            ->willReturn(
                [
                    new BundleConfig(ContaoManagerBundle::class),
                ]
            )
        ;

        $this->kernel->setBundleLoader($bundleLoader);

        include __DIR__.'/../Fixtures/HttpKernel/AppBundle.php';

        $bundles = $this->kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayHasKey('AppBundle\\AppBundle', $bundles);
    }

    /**
     * Tests the getRootDir() method.
     */
    public function testGetRootDir()
    {
        $this->assertEquals(dirname(dirname(dirname(dirname(dirname(__DIR__))))).'/app', $this->kernel->getRootDir());

        $this->kernel->setRootDir(__DIR__);

        $this->assertEquals(__DIR__, $this->kernel->getRootDir());
    }

    /**
     * Tests the getCacheDir() method.
     */
    public function testGetCacheDir()
    {
        $this->assertEquals(dirname($this->kernel->getRootDir()).'/var/cache/test', $this->kernel->getCacheDir());
    }

    /**
     * Tests the getLogDir() method.
     */
    public function testGetLogDir()
    {
        $this->assertEquals(dirname($this->kernel->getRootDir()).'/var/logs', $this->kernel->getLogDir());
    }

    /**
     * Tests the registerContainerConfiguration() method.
     *
     * @dataProvider containerConfigurationProvider
     */
    public function testRegisterContainerConfiguration($rootDir, $expectedResult)
    {
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

        $this->kernel->setRootDir($rootDir);
        $this->kernel->registerContainerConfiguration($loader);

        $this->assertEquals($expectedResult, $files);
    }

    /**
     * Provides data for the testRegisterContainerConfiguration method.
     *
     * @return array
     */
    public function containerConfigurationProvider()
    {
        return [
            [
                __DIR__.'/../Fixtures/HttpKernel/WithParametersYml',
                ['parameters.yml', 'parameters.yml'],
            ],
            [
                __DIR__.'/../Fixtures/HttpKernel/WithConfigDevYml',
                ['config_dev.yml'],
            ],
            [
                __DIR__.'/../Fixtures/HttpKernel/WithConfigYml',
                ['config.yml'],
            ],
            [
                __DIR__.'/../Fixtures/HttpKernel/WithConfigsYml',
                ['config_dev.yml'],
            ],
            [
                sys_get_temp_dir(),
                [],
            ],
        ];
    }

    /**
     * Tests the registerContainerConfiguration() method loads plugin configuration.
     */
    public function testRegisterContainerConfigurationLoadsPlugins()
    {
        $loader = $this->getMock(LoaderInterface::class);

        /** @var PluginLoader|\PHPUnit_Framework_MockObject_MockObject $pluginLoader */
        $pluginLoader = $this->getMockBuilder(PluginLoader::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pluginLoader
            ->expects($this->atLeastOnce())
            ->method('getInstancesOf')
            ->with(PluginLoader::CONFIG_PLUGINS)
            ->willReturn([$this->mockConfigPlugin($loader), $this->mockConfigPlugin($loader)])
        ;

        $this->kernel->setPluginLoader($pluginLoader);
        $this->kernel->setRootDir(sys_get_temp_dir());
        $this->kernel->registerContainerConfiguration($loader);
    }

    /**
     * Returns the config plugin.
     *
     * @param LoaderInterface $loader
     *
     * @return ConfigPluginInterface
     */
    private function mockConfigPlugin(LoaderInterface $loader)
    {
        $plugin = $this->getMock(ConfigPluginInterface::class);

        $plugin
            ->expects($this->once())
            ->method('registerContainerConfiguration')
            ->with($loader, [])
        ;

        return $plugin;
    }
}
