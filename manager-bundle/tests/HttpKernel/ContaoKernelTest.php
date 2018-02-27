<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
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

/**
 * Tests the ContaoKernel class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoKernelTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $kernel = $this->getKernel(__DIR__);

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoKernel', $kernel);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Kernel', $kernel);
    }

    /**
     * Tests the registerBundles() method.
     */
    public function testRegisterBundles()
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

        $kernel = $this->getKernel(sys_get_temp_dir());
        $kernel->setBundleLoader($bundleLoader);

        $bundles = $kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayNotHasKey(AppBundle::class, $bundles);
    }

    /**
     * Tests the registerBundles() method autoloads AppBundle.
     *
     * @runInSeparateProcess
     */
    public function testRegistersAppBundle()
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

        $kernel = $this->getKernel(sys_get_temp_dir());
        $kernel->setBundleLoader($bundleLoader);

        include __DIR__.'/../Fixtures/HttpKernel/AppBundle.php';

        $bundles = $kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayHasKey(AppBundle::class, $bundles);
    }

    /**
     * Tests the getCacheDir() method.
     */
    public function testGetCacheDir()
    {
        $kernel = $this->getKernel(sys_get_temp_dir());

        $this->assertSame($kernel->getProjectDir().'/var/cache/test', $kernel->getCacheDir());
    }

    /**
     * Tests the getLogDir() method.
     */
    public function testGetLogDir()
    {
        $kernel = $this->getKernel(sys_get_temp_dir());

        $this->assertSame($kernel->getProjectDir().'/var/logs', $kernel->getLogDir());
    }

    /**
     * Tests the registerContainerConfiguration() method.
     *
     * @param string $projectDir
     * @param string $expectedResult
     *
     * @dataProvider containerConfigurationProvider
     */
    public function testRegisterContainerConfiguration($projectDir, $expectedResult)
    {
        $files = [];
        $loader = $this->createMock(LoaderInterface::class);

        $loader
            ->expects($this->atLeastOnce())
            ->method('load')
            ->willReturnCallback(
                function ($resource) use (&$files) {
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

        $kernel = $this->getKernel($projectDir);
        $kernel->registerContainerConfiguration($loader);

        $this->assertSame($expectedResult, $files);
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
        $loader = $this->createMock(LoaderInterface::class);
        $pluginLoader = $this->createMock(PluginLoader::class);

        $pluginLoader
            ->expects($this->atLeastOnce())
            ->method('getInstancesOf')
            ->willReturn([$this->mockConfigPlugin($loader), $this->mockConfigPlugin($loader)])
        ;

        $kernel = $this->getKernel(sys_get_temp_dir());

        $kernel->setPluginLoader($pluginLoader);
        $kernel->registerContainerConfiguration($loader);
    }

    /**
     * Creates a kernel with PluginLoader for given project dir.
     *
     * @param string $projectDir
     *
     * @return ContaoKernel
     */
    private function getKernel($projectDir)
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
     * Returns the config plugin.
     *
     * @param LoaderInterface $loader
     *
     * @return ConfigPluginInterface
     */
    private function mockConfigPlugin(LoaderInterface $loader)
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
