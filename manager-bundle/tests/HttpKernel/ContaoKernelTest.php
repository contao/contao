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

use App\EventListener\FoobarListener;
use AppBundle\AppBundle;
use Composer\Autoload\ClassLoader;
use Contao\ManagerBundle\Api\ManagerConfig;
use Contao\ManagerBundle\ContaoManager\Plugin as ManagerPlugin;
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
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\ErrorHandler\DebugClassLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class ContaoKernelTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reset the ContaoKernel static properties
        $reflection = new \ReflectionClass(ContaoKernel::class);

        $prop = $reflection->getProperty('projectDir');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Reset the manager-bundle Plugin static properties
        $reflection = new \ReflectionClass(ManagerPlugin::class);

        $prop = $reflection->getProperty('autoloadModules');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Reset the Request static properties
        $reflection = new \ReflectionClass(Request::class);

        $prop = $reflection->getProperty('trustedProxies');
        $prop->setAccessible(true);
        $prop->setValue([]);

        $prop = $reflection->getProperty('trustedHostPatterns');
        $prop->setAccessible(true);
        $prop->setValue([]);

        $prop = $reflection->getProperty('trustedHeaderSet');
        $prop->setAccessible(true);
        $prop->setValue(-1);

        $prop = $reflection->getProperty('httpMethodParameterOverride');
        $prop->setAccessible(true);
        $prop->setValue(false);

        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithAppNamespace/var');
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithInvalidNamespace/var');
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithMixedNamespace/var');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithAppNamespace/var');
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithInvalidNamespace/var');
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithMixedNamespace/var');
    }

    public function testRegisterBundles(): void
    {
        $bundleLoader = $this->createMock(BundleLoader::class);
        $bundleLoader
            ->expects($this->once())
            ->method('getBundleConfigs')
            ->willReturn([new BundleConfig(ContaoManagerBundle::class)])
        ;

        $kernel = $this->getKernel($this->getTempDir());
        $kernel->setBundleLoader($bundleLoader);

        $bundles = $kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayNotHasKey(AppBundle::class, $bundles);
    }

    public function testRegistersAppBundle(): void
    {
        $bundleLoader = $this->createMock(BundleLoader::class);
        $bundleLoader
            ->expects($this->once())
            ->method('getBundleConfigs')
            ->willReturn([new BundleConfig(ContaoManagerBundle::class)])
        ;

        $kernel = $this->getKernel($this->getTempDir());
        $kernel->setBundleLoader($bundleLoader);

        include __DIR__.'/../Fixtures/HttpKernel/AppBundle.php';

        $bundles = $kernel->registerBundles();

        $this->assertArrayHasKey(ContaoManagerBundle::class, $bundles);
        $this->assertArrayHasKey(AppBundle::class, $bundles);
    }

    public function testThrowsExceptionIfProjectDirIsNotSet(): void
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('ContaoKernel::setProjectDir() must be called to initialize the Contao kernel');

        $kernel = new ContaoKernel('prod', false);
        $kernel->getProjectDir();
    }

    public function testGetRootDir(): void
    {
        $kernel = $this->getKernel($this->getTempDir());

        $this->assertSame($kernel->getProjectDir().'/app', $kernel->getRootDir());
    }

    public function testGetCacheDir(): void
    {
        $kernel = $this->getKernel($this->getTempDir());

        $this->assertSame($kernel->getProjectDir().'/var/cache/prod', $kernel->getCacheDir());
    }

    public function testGetLogDir(): void
    {
        $kernel = $this->getKernel($this->getTempDir());

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
                static function ($resource) use (&$files): void {
                    $files[] = basename($resource);
                }
            )
        ;

        $kernel = $this->getKernel($projectDir, $env);
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
            ['config_prod.yml', 'services.yml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithAppNamespace',
            'prod',
            ['services.php'],
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

        $kernel = $this->getKernel($this->getTempDir());
        $kernel->setPluginLoader($pluginLoader);
        $kernel->registerContainerConfiguration($loader);
    }

    public function testIntegrationAutoloadAppNamespace(): void
    {
        $classLoader = new ClassLoader();
        $classLoader->addPsr4('App\\', __DIR__.'/../Fixtures/HttpKernel/WithAppNamespace/src');
        $classLoader->register();

        $pluginLoader = new PluginLoader(null, [
            'contao/core-bundle' => new CoreBundlePlugin(),
            'contao/manager-bundle' => new ManagerBundlePlugin(),
        ]);

        ContaoKernel::setProjectDir(__DIR__.'/../Fixtures/HttpKernel/WithAppNamespace');
        $kernel = new ContaoKernel('prod', false);
        $kernel->setPluginLoader($pluginLoader)
        $kernel->boot();

        $container = $kernel->getContainer();

        $this->assertTrue($container->has('App\\EventListener\\FoobarListener'));
        $this->assertInstanceOf(FoobarListener::class, $container->get('App\\EventListener\\FoobarListener'));

        $kernel->shutdown();
        $classLoader->unregister();
    }

    /**
     * @runInSeparateProcess
     */
    public function testIntegrationAutoloadIsIgnoredWithoutClassloader(): void
    {
        $pluginLoader = new PluginLoader(null, [
            'contao/core-bundle' => new CoreBundlePlugin(),
            'contao/manager-bundle' => new ManagerBundlePlugin(),
        ]);

        ContaoKernel::setProjectDir(__DIR__.'/../Fixtures/HttpKernel/WithAppNamespace');
        $kernel = new ContaoKernel('prod', false);
        $kernel->setPluginLoader($pluginLoader)
        $kernel->boot();

        $container = $kernel->getContainer();

        $this->assertFalse($container->has('App\\EventListener\\FoobarListener'));

        $kernel->shutdown();
    }

    /**
     * @runInSeparateProcess
     */
    public function testIntegrationAutoloadIgnoresInvalidNamespace(): void
    {
        $classLoader = new ClassLoader();
        $classLoader->addPsr4('App\\', __DIR__.'/../Fixtures/HttpKernel/WithInvalidNamespace/src');
        $classLoader->register();

        $pluginLoader = new PluginLoader(null, [
            'contao/core-bundle' => new CoreBundlePlugin(),
            'contao/manager-bundle' => new ManagerBundlePlugin(),
        ]);

        ContaoKernel::setProjectDir(__DIR__.'/../Fixtures/HttpKernel/WithInvalidNamespace');
        $kernel = new ContaoKernel('prod', false);
        $kernel->setPluginLoader($pluginLoader)
        $kernel->boot();

        $container = $kernel->getContainer();

        $this->assertFalse($container->has('App\\EventListener\\FoobarListener'));

        $kernel->shutdown();
        $classLoader->unregister();
    }

    /**
     * @runInSeparateProcess
     */
    public function testIntegrationAutoloadIgnoresDebugExceptionOnInvalidNamespace(): void
    {
        $classLoader = new ClassLoader();
        $classLoader->addPsr4('App\\', __DIR__.'/../Fixtures/HttpKernel/WithInvalidNamespace/src');
        $classLoader->register();

        $pluginLoader = new PluginLoader(null, [
            'contao/core-bundle' => new CoreBundlePlugin(),
            'contao/manager-bundle' => new ManagerBundlePlugin(),
        ]);

        DebugClassLoader::enable();

        ContaoKernel::setProjectDir(__DIR__.'/../Fixtures/HttpKernel/WithInvalidNamespace');
        $kernel = new ContaoKernel('dev', true);
        $kernel->setPluginLoader($pluginLoader)
        $kernel->boot();

        $container = $kernel->getContainer();

        $this->assertFalse($container->has('App\\EventListener\\FoobarListener'));

        $kernel->shutdown();
        DebugClassLoader::disable();
        $classLoader->unregister();
    }

    public function testIntegrationAutoloadThrowsDebugExceptionOnMixedNamespace(): void
    {
        $classLoader = new ClassLoader();
        $classLoader->addPsr4('App\\', __DIR__.'/../Fixtures/HttpKernel/WithMixedNamespace/src');
        $classLoader->register();

        $pluginLoader = new PluginLoader(null, [
            'contao/core-bundle' => new CoreBundlePlugin(),
            'contao/manager-bundle' => new ManagerBundlePlugin(),
        ]);

        DebugClassLoader::enable();

        ContaoKernel::setProjectDir(__DIR__.'/../Fixtures/HttpKernel/WithMixedNamespace');
        $kernel = new ContaoKernel('dev', true);
        $kernel->setPluginLoader($pluginLoader)

        try {
            $kernel->boot();
        } catch (\Exception $e) {
            $this->assertInstanceOf(\RuntimeException::class, $e);

            return;
        } finally {
            $kernel->shutdown();
            DebugClassLoader::disable();
            $classLoader->unregister();
        }

        $this->fail(__METHOD__.' did not throw expected exception');
    }

    public function testGetHttpCache(): void
    {
        $kernel = $this->getKernel($this->getTempDir());
        $cache = $kernel->getHttpCache();

        $this->assertSame($cache, $kernel->getHttpCache());
    }

    public function testSetsRequestTrustedProxiesFromEnvVars(): void
    {
        $this->assertSame([], Request::getTrustedProxies());
        $this->assertSame(-1, Request::getTrustedHeaderSet());

        $_SERVER['TRUSTED_PROXIES'] = '1.1.1.1,2.2.2.2';

        ContaoKernel::fromRequest($this->getTempDir(), Request::create('/'));

        $this->assertSame(['1.1.1.1', '2.2.2.2'], Request::getTrustedProxies());
        $this->assertSame(Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST, Request::getTrustedHeaderSet());
    }

    public function testSetsRequestTrustedHostsFromEnvVars(): void
    {
        $this->assertSame([], Request::getTrustedHosts());

        $_SERVER['TRUSTED_HOSTS'] = '1.1.1.1,2.2.2.2';

        ContaoKernel::fromRequest($this->getTempDir(), Request::create('/'));

        $this->assertSame(['{1.1.1.1}i', '{2.2.2.2}i'], Request::getTrustedHosts());
    }

    public function testEnablesRequestHttpMethodParameterOverride(): void
    {
        $this->assertFalse(Request::getHttpMethodParameterOverride());

        ContaoKernel::fromRequest($this->getTempDir(), Request::create('/'));

        $this->assertTrue(Request::getHttpMethodParameterOverride());
    }

    public function testSetsProjectDirFromInput(): void
    {
        $tempDir = realpath($this->getTempDir());
        $kernel = ContaoKernel::fromInput($tempDir, new ArgvInput(['help']));

        $this->assertSame($tempDir, $kernel->getProjectDir());
    }

    public function testCreatesProdKernelWithoutConsoleArgument(): void
    {
        $input = new ArgvInput(['help']);
        $kernel = ContaoKernel::fromInput($this->getTempDir(), $input);

        $this->assertSame('prod', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreatesDevKernelFromConsoleArgument(): void
    {
        $input = new ArgvInput(['help', '--env=dev']);
        $kernel = ContaoKernel::fromInput($this->getTempDir(), $input);

        $this->assertSame('dev', $kernel->getEnvironment());
        $this->assertTrue($kernel->isDebug());
    }

    public function testThrowsExceptionOnInvalidConsoleEnvironment(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The Contao Managed Edition only supports the "dev" and "prod" environments');

        $input = new ArgvInput(['list', '--env=foo']);
        ContaoKernel::fromInput($this->getTempDir(), $input);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReturnsTheContaoKernelInDevMode(): void
    {
        $_SERVER['APP_ENV'] = 'dev';

        $tempDir = realpath($this->getTempDir());
        $kernel = ContaoKernel::fromRequest($tempDir, Request::create('/'));

        $this->assertInstanceOf(ContaoKernel::class, $kernel);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testReturnsTheContaoCacheInProdMode(): void
    {
        unset($_SERVER['APP_ENV']);

        $tempDir = realpath($this->getTempDir());
        $kernel = ContaoKernel::fromRequest($tempDir, Request::create('/'));

        $this->assertInstanceOf(ContaoCache::class, $kernel);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreatesDevKernelFromAppEnvVar(): void
    {
        $_SERVER['APP_ENV'] = 'dev';

        $input = new ArgvInput(['help']);
        $kernel = ContaoKernel::fromInput($this->getTempDir(), $input);

        $this->assertSame('dev', $kernel->getEnvironment());
        $this->assertTrue($kernel->isDebug());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreatesProdKernelFromAppEnvVar(): void
    {
        $_SERVER['APP_ENV'] = 'prod';

        $input = new ArgvInput(['help']);
        $kernel = ContaoKernel::fromInput($this->getTempDir(), $input);

        $this->assertSame('prod', $kernel->getEnvironment());
        $this->assertFalse($kernel->isDebug());
    }

    /**
     * Returns a kernel with a plugin loader mock.
     *
     * @return ContaoKernel&MockObject
     */
    private function getKernel(string $projectDir, string $env = 'prod'): ContaoKernel
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
     * @return ConfigPluginInterface&MockObject
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
