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
use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerBundle\HttpKernel\ContaoCache;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Config\Loader\LoaderInterface;

class ContaoKernelTest extends ContaoTestCase
{
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
     * @preserveGlobalState disabled
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

    public function testGetProjectDir(): void
    {
        $kernel = $this->mockKernel($this->getTempDir());

        $this->assertSame($kernel->getProjectDir(), $kernel->getProjectDir());
    }

    public function testGetRootDir(): void
    {
        $kernel = $this->mockKernel($this->getTempDir());

        $this->assertSame($kernel->getProjectDir().'/app', $kernel->getRootDir());
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

    public function testSetsDisabledPackagesInPluginLoader(): void
    {
        $config = $this->createMock(ManagerConfig::class);
        $config
            ->expects($this->once())
            ->method('all')
            ->willReturn([
                'contao_manager' => [
                    'disabled_packages' => ['foo/bar'],
                ],
            ])
        ;

        ContaoKernel::setProjectDir($this->getTempDir());

        $kernel = new ContaoKernel('prod', true);
        $kernel->setManagerConfig($config);

        $pluginLoader = $kernel->getPluginLoader();

        $this->assertSame(['foo/bar'], $pluginLoader->getDisabledPackages());
    }

    /**
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
                    $files[] = basename($resource);
                }
            )
        ;

        $kernel = $this->mockKernel($projectDir, $env);
        $kernel->registerContainerConfiguration($loader);

        $this->assertSame($expectedResult, $files);
    }

    public function containerConfigurationProvider(): \Generator
    {
        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithParametersYml',
            'prod',
            ['parameters.yml', 'parameters.yml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigDevYml',
            'dev',
            ['config_dev.yml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigYml',
            'prod',
            ['config.yml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigsYml',
            'prod',
            ['config_prod.yml'],
        ];

        yield [
            $this->getTempDir(),
            'prod',
            [],
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

    public function testGetHttpCache(): void
    {
        $kernel = $this->mockKernel($this->getTempDir());

        $this->assertInstanceOf(ContaoCache::class, $kernel->getHttpCache());
    }

    /**
     * Mocks a kernel with the plugin loader.
     *
     * @return ContaoKernel|MockObject
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
     * @return ConfigPluginInterface|MockObject
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
