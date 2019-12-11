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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\ContaoManager\Plugin;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder as PluginContainerBuilder;
use Contao\ManagerPlugin\PluginLoader;
use Contao\TestCase\ContaoTestCase;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle;
use FOS\HttpCacheBundle\FOSHttpCacheBundle;
use Lexik\Bundle\MaintenanceBundle\LexikMaintenanceBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PluginTest extends ContaoTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (isset($_SERVER['APP_SECRET'])) {
            $_SERVER['APP_SECRET_ORIG'] = $_SERVER['APP_SECRET'];
            unset($_SERVER['APP_SECRET']);
        }

        if (isset($_SERVER['DATABASE_URL'])) {
            $_SERVER['DATABASE_URL_ORIG'] = $_SERVER['DATABASE_URL'];
            unset($_SERVER['DATABASE_URL']);
        }
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        if (isset($_SERVER['APP_SECRET_ORIG'])) {
            $_SERVER['APP_SECRET'] = $_SERVER['APP_SECRET_ORIG'];
            unset($_SERVER['APP_SECRET_ORIG']);
        }

        if (isset($_SERVER['DATABASE_URL_ORIG'])) {
            $_SERVER['DATABASE_URL'] = $_SERVER['DATABASE_URL_ORIG'];
            unset($_SERVER['DATABASE_URL_ORIG']);
        }
    }

    public function testReturnsTheBundles(): void
    {
        $plugin = new Plugin();

        /** @var BundleConfig[] $bundles */
        $bundles = $plugin->getBundles(new DelegatingParser());

        $this->assertCount(14, $bundles);

        $this->assertSame(FrameworkBundle::class, $bundles[0]->getName());
        $this->assertSame([], $bundles[0]->getReplace());
        $this->assertSame([], $bundles[0]->getLoadAfter());

        $this->assertSame(SecurityBundle::class, $bundles[1]->getName());
        $this->assertSame([], $bundles[1]->getReplace());
        $this->assertSame([], $bundles[1]->getLoadAfter());

        $this->assertSame(TwigBundle::class, $bundles[2]->getName());
        $this->assertSame([], $bundles[2]->getReplace());
        $this->assertSame([], $bundles[2]->getLoadAfter());

        $this->assertSame(MonologBundle::class, $bundles[3]->getName());
        $this->assertSame([], $bundles[3]->getReplace());
        $this->assertSame([], $bundles[3]->getLoadAfter());

        $this->assertSame(SwiftmailerBundle::class, $bundles[4]->getName());
        $this->assertSame([], $bundles[4]->getReplace());
        $this->assertSame([], $bundles[4]->getLoadAfter());

        $this->assertSame(DoctrineBundle::class, $bundles[5]->getName());
        $this->assertSame([], $bundles[5]->getReplace());
        $this->assertSame([], $bundles[5]->getLoadAfter());

        $this->assertSame(DoctrineCacheBundle::class, $bundles[6]->getName());
        $this->assertSame([], $bundles[6]->getReplace());
        $this->assertSame([], $bundles[6]->getLoadAfter());

        $this->assertSame(LexikMaintenanceBundle::class, $bundles[7]->getName());
        $this->assertSame([], $bundles[7]->getReplace());
        $this->assertSame([], $bundles[7]->getLoadAfter());

        $this->assertSame(NelmioCorsBundle::class, $bundles[8]->getName());
        $this->assertSame([], $bundles[8]->getReplace());
        $this->assertSame([], $bundles[8]->getLoadAfter());

        $this->assertSame(NelmioSecurityBundle::class, $bundles[9]->getName());
        $this->assertSame([], $bundles[9]->getReplace());
        $this->assertSame([], $bundles[9]->getLoadAfter());

        $this->assertSame(FOSHttpCacheBundle::class, $bundles[10]->getName());
        $this->assertSame([], $bundles[10]->getReplace());
        $this->assertSame([], $bundles[10]->getLoadAfter());

        $this->assertSame(ContaoManagerBundle::class, $bundles[11]->getName());
        $this->assertSame([], $bundles[11]->getReplace());
        $this->assertSame([ContaoCoreBundle::class], $bundles[11]->getLoadAfter());

        $this->assertSame(DebugBundle::class, $bundles[12]->getName());
        $this->assertSame([], $bundles[12]->getReplace());
        $this->assertSame([], $bundles[12]->getLoadAfter());
        $this->assertFalse($bundles[12]->loadInProduction());

        $this->assertSame(WebProfilerBundle::class, $bundles[13]->getName());
        $this->assertSame([], $bundles[13]->getReplace());
        $this->assertSame([], $bundles[13]->getLoadAfter());
        $this->assertFalse($bundles[13]->loadInProduction());
    }

    public function testRegistersModuleBundles(): void
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
                static function ($resource): array {
                    return [$resource];
                }
            )
        ;

        $plugin = new Plugin();
        $configs = $plugin->getBundles($parser);

        $this->assertCount(16, $configs);
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
                static function ($resource) use (&$files): void {
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
                static function ($resource) use (&$files): void {
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
                static function (string $file): RouteCollection {
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

    public function testReturnsApiCommands(): void
    {
        $files = Finder::create()
            ->name('*.php')
            ->in(__DIR__.'/../../src/ContaoManager/ApiCommand')
        ;

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $this->assertContains(
                'Contao\ManagerBundle\ContaoManager\ApiCommand\\'.$file->getBasename('.php'),
                (new Plugin())->getApiCommands()
            );
        }
    }

    public function testReturnsApiFeatures(): void
    {
        $this->assertSame(
            [
                'dot-env' => [
                    'TRUSTED_PROXIES',
                    'TRUSTED_HOSTS',
                ],
                'config' => [
                    'disable-packages',
                ],
                'jwt-cookie' => [
                    'debug',
                ],
            ],
            (new Plugin())->getApiFeatures()
        );
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Defining the "prepend_locale" parameter in the parameters.yml file %s.
     */
    public function testHandlesThePrependLocaleParameter(): void
    {
        $container = $this->getContainer();
        $container->setParameter('prepend_locale', true);

        $expect = [[
            'prepend_locale' => '%prepend_locale%',
        ]];

        $extensionConfig = (new Plugin())->getExtensionConfig('contao', [], $container);

        $this->assertSame($expect, $extensionConfig);
    }

    public function testSetsTheAppSecret(): void
    {
        $container = $this->getContainer();

        (new Plugin())->getExtensionConfig('framework', [], $container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame('ThisTokenIsNotSoSecretChangeIt', $bag['env(APP_SECRET)']);
    }

    /**
     * @dataProvider getDatabaseParameters
     */
    public function testSetsTheDatabaseUrl(?string $user, ?string $password, ?string $name, string $expected): void
    {
        $container = $this->getContainer();
        $container->setParameter('database_user', $user);
        $container->setParameter('database_password', $password);
        $container->setParameter('database_name', $name);

        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'pdo_mysql',
                        ],
                    ],
                ],
            ],
        ];

        (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame($expected, $bag['env(DATABASE_URL)']);
    }

    public function getDatabaseParameters(): \Generator
    {
        yield [
            null,
            null,
            null,
            'mysql://localhost:3306',
        ];

        yield [
            null,
            null,
            'contao_test',
            'mysql://localhost:3306/contao_test',
        ];

        yield [
            null,
            'foobar',
            'contao_test',
            'mysql://localhost:3306/contao_test',
        ];

        yield [
            'root',
            null,
            'contao_test',
            'mysql://root@localhost:3306/contao_test',
        ];

        yield [
            'root',
            'foobar',
            'contao_test',
            'mysql://root:foobar@localhost:3306/contao_test',
        ];
    }

    public function testAddsTheDefaultServerVersion(): void
    {
        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'pdo_mysql',
                        ],
                    ],
                ],
            ],
        ];

        $expect = array_merge(
            $extensionConfigs,
            [[
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'server_version' => '5.5',
                        ],
                    ],
                ],
            ]]
        );

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $this->assertSame($expect, $extensionConfig);
    }

    public function testUpdatesTheMailerTransport(): void
    {
        $container = $this->getContainer();
        $container->setParameter('mailer_transport', 'mail');

        (new Plugin())->getExtensionConfig('swiftmailer', [], $container);

        $this->assertSame('sendmail', $container->getParameter('mailer_transport'));
    }

    /**
     * @dataProvider getMailerParameters
     */
    public function testSetsTheMailerUrl(string $transport, string $host, ?string $user, ?string $password, int $port, ?string $encryption, string $expected): void
    {
        $container = $this->getContainer();
        $container->setParameter('mailer_transport', $transport);
        $container->setParameter('mailer_host', $host);
        $container->setParameter('mailer_user', $user);
        $container->setParameter('mailer_password', $password);
        $container->setParameter('mailer_port', $port);
        $container->setParameter('mailer_encryption', $encryption);

        (new Plugin())->getExtensionConfig('swiftmailer', [], $container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame($expected, $bag['env(MAILER_URL)']);
    }

    public function getMailerParameters(): \Generator
    {
        yield [
            'sendmail',
            '127.0.0.1',
            null,
            null,
            25,
            null,
            'sendmail://localhost',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            null,
            null,
            25,
            null,
            'smtp://127.0.0.1:25',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            null,
            'foobar',
            25,
            null,
            'smtp://127.0.0.1:25',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            null,
            25,
            null,
            'smtp://127.0.0.1:25?username=foo%40bar.com',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            25,
            null,
            'smtp://127.0.0.1:25?username=foo%40bar.com&password=foobar',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            null,
            null,
            587,
            'tls',
            'smtp://127.0.0.1:587?encryption=tls',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            587,
            'tls',
            'smtp://127.0.0.1:587?username=foo%40bar.com&password=foobar&encryption=tls',
        ];
    }

    private function getContainer(): PluginContainerBuilder
    {
        $pluginLoader = $this->createMock(PluginLoader::class);

        $container = new PluginContainerBuilder($pluginLoader, []);
        $container->setParameter('database_host', 'localhost');
        $container->setParameter('database_port', 3306);
        $container->setParameter('database_user', null);
        $container->setParameter('database_password', null);
        $container->setParameter('database_name', null);
        $container->setParameter('mailer_transport', 'sendmail');
        $container->setParameter('mailer_host', '127.0.0.1');
        $container->setParameter('mailer_user', null);
        $container->setParameter('mailer_password', null);
        $container->setParameter('mailer_port', 25);
        $container->setParameter('mailer_encryption', null);
        $container->setParameter('secret', 'ThisTokenIsNotSoSecretChangeIt');

        return $container;
    }
}
