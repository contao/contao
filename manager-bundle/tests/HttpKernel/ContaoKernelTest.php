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
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\ClassExistenceResource;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;

class ContaoKernelTest extends ContaoTestCase
{
    use ExpectDeprecationTrait;

    private array|string|false $shellVerbosityBackup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupServerEnvGetPost();

        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'prod';

        $this->shellVerbosityBackup = getenv('SHELL_VERBOSITY');
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithAppNamespace/var');
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithInvalidNamespace/var');
        $filesystem->remove(__DIR__.'/../Fixtures/HttpKernel/WithMixedNamespace/var');

        putenv('SHELL_VERBOSITY'.(false === $this->shellVerbosityBackup ? '' : '='.$this->shellVerbosityBackup));

        $this->restoreServerEnvGetPost();
        $this->resetStaticProperties([ManagerPlugin::class, ContaoKernel::class, Request::class, EnvPlaceholderParameterBag::class, ClassExistenceResource::class]);

        parent::tearDown();
    }

    public function testResetsTheBundleLoaderOnShutdown(): void
    {
        $bundleLoader = $this->createMock(BundleLoader::class);

        $kernel = $this->getKernel($this->getTempDir());
        $kernel->setBundleLoader($bundleLoader);
        $kernel->boot();

        $this->assertSame($bundleLoader, $kernel->getBundleLoader());

        $kernel->shutdown();

        $this->assertNotSame($bundleLoader, $kernel->getBundleLoader());
    }

    public function testDoesNotResetsTheBundleLoaderOnShutdownIfKernelIsNotBooted(): void
    {
        $bundleLoader = $this->createMock(BundleLoader::class);

        $kernel = $this->getKernel($this->getTempDir());
        $kernel->setBundleLoader($bundleLoader);

        $this->assertSame($bundleLoader, $kernel->getBundleLoader());

        $kernel->shutdown();

        $this->assertSame($bundleLoader, $kernel->getBundleLoader());
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

    public function testGetCacheDir(): void
    {
        $kernel = $this->getKernel($this->getTempDir());

        $this->assertSame(
            Path::normalize($kernel->getProjectDir()).'/var/cache/prod',
            Path::normalize($kernel->getCacheDir()),
        );
    }

    public function testGetLogDir(): void
    {
        $kernel = $this->getKernel($this->getTempDir());

        $this->assertSame(
            Path::normalize($kernel->getProjectDir()).'/var/logs',
            Path::normalize($kernel->getLogDir()),
        );
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

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('fileExists')
            ->willReturnCallback(static fn (string $path) => \in_array(basename($path), $expectedResult, true))
        ;

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->method('load')
            ->willReturnCallback(
                static function ($resource) use ($container, $env, &$files) {
                    if ($resource instanceof \Closure) {
                        return $resource($container, $env);
                    }

                    $files[] = basename($resource);

                    return null;
                },
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
            ['parameters.yaml', 'parameters.yaml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigDevYml',
            'dev',
            ['config_dev.yaml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigYml',
            'prod',
            ['config.yaml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigsYml',
            'prod',
            ['config_prod.yaml', 'services.yaml'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigsPhp',
            'prod',
            ['services.php'],
        ];

        yield [
            __DIR__.'/../Fixtures/HttpKernel/WithConfigsXml',
            'prod',
            ['services.xml'],
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
        $container = $this->createMock(ContainerBuilder::class);

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->method('load')
            ->willReturnCallback(
                static function ($resource) use ($container) {
                    if ($resource instanceof \Closure) {
                        return $resource($container, 'prod');
                    }

                    return null;
                },
            )
        ;

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
        $this->assertSame(Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO, Request::getTrustedHeaderSet());

        unset($_SERVER['TRUSTED_PROXIES']);
    }

    public function testSetsRequestTrustedHostsFromEnvVars(): void
    {
        $this->assertSame([], Request::getTrustedHosts());

        $_SERVER['TRUSTED_PROXIES'] = '1.1.1.1,2.2.2.2';
        $_SERVER['TRUSTED_HOSTS'] = '1.1.1.1,2.2.2.2,example.com';

        ContaoKernel::fromRequest($this->getTempDir(), Request::create('/'));

        $this->assertSame(['{1.1.1.1}i', '{2.2.2.2}i', '{example.com}i'], Request::getTrustedHosts());
        $this->assertSame(Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_HOST, Request::getTrustedHeaderSet());

        unset($_SERVER['TRUSTED_HOSTS']);
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
        unset($_SERVER['APP_ENV'], $_ENV['APP_ENV'], $_ENV['DISABLE_HTTP_CACHE'], $_SERVER['DISABLE_HTTP_CACHE']);

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

    private function mockConfigPlugin(LoaderInterface $loader): ConfigPluginInterface&MockObject
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
