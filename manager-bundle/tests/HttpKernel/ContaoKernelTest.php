<?php

declare(strict_types=1);

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
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoKernelTest extends ContaoTestCase
{
    public function testInstantiation(): void
    {
        $kernel = $this->mockKernel($this->getTempDir());

        $this->assertInstanceOf('Contao\ManagerBundle\HttpKernel\ContaoKernel', $kernel);
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Kernel', $kernel);
    }

    public function testRegisterBundles(): void
    {
        $bundleLoader = $this->createMock(BundleLoader::class);

        $bundleLoader
            ->expects($this->once())
            ->method('getBundleConfigs')
            ->willReturn([new BundleConfig(ContaoManagerBundle::class)])
        ;

        $kernel = $this->mockKernel($this->getTempDir());
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
            ->willReturn([new BundleConfig(ContaoManagerBundle::class)])
        ;

        $kernel = $this->mockKernel($this->getTempDir());
        $kernel->setBundleLoader($bundleLoader);

        include __DIR__.'/../Fixtures/HttpKernel/AppBundle.php';

        $bundles = $kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayHasKey(AppBundle::class, $bundles);
    }

    public function testGetCacheDir(): void
    {
        $kernel = $this->mockKernel($this->getTempDir());

        $this->assertSame($kernel->getProjectDir().'/var/cache/prod', $kernel->getCacheDir());
    }

    public function testGetLogDir(): void
    {
        $kernel = $this->mockKernel($this->getTempDir());

        $this->assertSame($kernel->getProjectDir().'/var/logs', $kernel->getLogDir());
    }

    /**
     * @param string $projectDir
     * @param string $env
     * @param array  $expectedResult
     *
     * @dataProvider containerConfigurationProvider
     */
    public function testRegisterContainerConfiguration(string $projectDir, string $env, array $expectedResult): void
    {
        $files = [];
        $loader = $this->createMock(LoaderInterface::class);

        $loader
            ->method('load')
            ->willReturnCallback(
                function ($resource) use (&$files): void {
                    if (\is_string($resource)) {
                        $files[] = basename($resource);
                    } elseif (\is_callable($resource)) {
                        $container = new ContainerBuilder();
                        $container->setParameter('mailer_transport', 'sendmail');

                        $resource($container);
                    }
                }
            )
        ;

        $kernel = $this->mockKernel($projectDir, $env);
        $kernel->registerContainerConfiguration($loader);

        $this->assertSame($expectedResult, $files);
    }

    /**
     * @return array
     */
    public function containerConfigurationProvider(): array
    {
        return [
            [
                __DIR__.'/../Fixtures/HttpKernel/WithParametersYml',
                'prod',
                ['parameters.yml', 'parameters.yml'],
            ],
            [
                __DIR__.'/../Fixtures/HttpKernel/WithConfigDevYml',
                'dev',
                ['config_dev.yml'],
            ],
            [
                __DIR__.'/../Fixtures/HttpKernel/WithConfigYml',
                'prod',
                ['config.yml'],
            ],
            [
                __DIR__.'/../Fixtures/HttpKernel/WithConfigsYml',
                'prod',
                ['config_prod.yml'],
            ],
            [
                $this->getTempDir(),
                'prod',
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

        $kernel = $this->mockKernel($this->getTempDir());

        $kernel->setPluginLoader($pluginLoader);
        $kernel->registerContainerConfiguration($loader);
    }

    public function testUpdatesTheMailerTransport(): void
    {
        $container = new ContainerBuilder();
        $loader = $this->createMock(LoaderInterface::class);

        $loader
            ->method('load')
            ->willReturnCallback(
                function ($resource) use ($container): void {
                    if (\is_callable($resource)) {
                        $container->setParameter('mailer_transport', 'mail');

                        $resource($container);
                    }
                }
            )
        ;

        $kernel = $this->mockKernel($this->getTempDir());
        $kernel->registerContainerConfiguration($loader);

        $this->assertSame('sendmail', $container->getParameter('mailer_transport'));
    }

    /**
     * Mocks a kernel with the plugin loader.
     *
     * @param string $projectDir
     * @param string $env
     *
     * @return ContaoKernel|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockKernel(string $projectDir, string $env = 'prod'): ContaoKernel
    {
        $pluginLoader = $this->createMock(PluginLoader::class);

        $pluginLoader
            ->method('getInstancesOf')
            ->willReturn([])
        ;

        ContaoKernel::setProjectDir($projectDir);

        $kernel = new ContaoKernel($env, true);
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
