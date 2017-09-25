<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Tests\HttpKernel;

use AppBundle\AppBundle;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoKernelTest extends TestCase
{
    public function testInstantiation(): void
    {
        $kernel = $this->mockKernel(__DIR__);

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoKernel', $kernel);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Kernel', $kernel);
    }

    public function testRegisterBundles(): void
    {
        $bundleLoader = $this->createMock(BundleLoader::class);

        $bundleLoader
            ->expects($this->once())
            ->method('getBundleConfigs')
            ->willReturn(
                [
                    new BundleConfig(ContaoManagerBundle::class),
                ]
            )
        ;

        $kernel = $this->mockKernel(sys_get_temp_dir());
        $kernel->setBundleLoader($bundleLoader);

        $bundles = $kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayNotHasKey(AppBundle::class, $bundles);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRegistersAppBundle(): void
    {
        $bundleLoader = $this->createMock(BundleLoader::class);

        $bundleLoader
            ->expects($this->once())
            ->method('getBundleConfigs')
            ->willReturn(
                [
                    new BundleConfig(ContaoManagerBundle::class),
                ]
            )
        ;

        $kernel = $this->mockKernel(sys_get_temp_dir());
        $kernel->setBundleLoader($bundleLoader);

        include __DIR__.'/../Fixtures/HttpKernel/AppBundle.php';

        $bundles = $kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayHasKey(AppBundle::class, $bundles);
    }

    public function testGetCacheDir(): void
    {
        $kernel = $this->mockKernel(sys_get_temp_dir());

        $this->assertSame($kernel->getProjectDir().'/var/cache/test', $kernel->getCacheDir());
    }

    public function testGetLogDir(): void
    {
        $kernel = $this->mockKernel(sys_get_temp_dir());

        $this->assertSame($kernel->getProjectDir().'/var/logs', $kernel->getLogDir());
    }

    /**
     * @param string $projectDir
     * @param string $expectedResult
     *
     * @dataProvider containerConfigurationProvider
     */
    public function testRegisterContainerConfiguration($projectDir, $expectedResult): void
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
                    } elseif (is_callable($resource)) {
                        $container = new ContainerBuilder();
                        $container->setParameter('kernel.environment', 'dev');
                        \call_user_func($resource, $container);
                    }
                }
            )
        ;

        $kernel = $this->mockKernel($projectDir);
        $kernel->registerContainerConfiguration($loader);

        $this->assertSame($expectedResult, $files);
    }

    /**
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

    public function testRegisterContainerConfigurationLoadsPlugins(): void
    {
        $loader = $this->createMock(LoaderInterface::class);
        $pluginLoader = $this->createMock(PluginLoader::class);

        $pluginLoader
            ->expects($this->atLeastOnce())
            ->method('getInstancesOf')
            ->willReturn([$this->mockConfigPlugin($loader), $this->mockConfigPlugin($loader)])
        ;

        $kernel = $this->mockKernel(sys_get_temp_dir());

        $kernel->setPluginLoader($pluginLoader);
        $kernel->registerContainerConfiguration($loader);
    }

    /**
     * Mocks a kernel with the plugin loader.
     *
     * @param string $projectDir
     *
     * @return ContaoKernel|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockKernel($projectDir): ContaoKernel
    {
        $pluginLoader = $this->createMock(PluginLoader::class);

        $pluginLoader
            ->method('getInstancesOf')
            ->willReturn([])
        ;

        ContaoKernel::setProjectDir($projectDir);
        $kernel = new ContaoKernel('test', true);
        $kernel->setPluginLoader($pluginLoader);

        return $kernel;
    }

    /**
     * Mocks a configuartion plugin.
     *
     * @param LoaderInterface $loader
     *
     * @return ConfigPluginInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigPlugin(LoaderInterface $loader): ConfigPluginInterface
    {
        $plugin = $this->createMock(ConfigPluginInterface::class);

        $plugin
            ->expects($this->once())
            ->method('registerContainerConfiguration')
            ->with($loader, [])
        ;

        return $plugin;
    }
}
