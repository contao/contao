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
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder as PluginContainerBuilder;
use Contao\ManagerPlugin\Dependency\DependentPluginInterface;
use Contao\ManagerPlugin\PluginLoader;
use Contao\TestCase\ContaoTestCase;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FOS\HttpCacheBundle\FOSHttpCacheBundle;
use League\FlysystemBundle\FlysystemBundle;
use Nelmio\CorsBundle\NelmioCorsBundle;
use Nelmio\SecurityBundle\NelmioSecurityBundle;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\Transport\NativeTransportFactory;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

/**
 * @backupGlobals enabled
 */
class PluginTest extends ContaoTestCase
{
    use ExpectDeprecationTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupServerEnvGetPost();

        unset($_SERVER['DATABASE_URL'], $_SERVER['APP_SECRET'], $_ENV['DATABASE_URL']);
    }

    protected function tearDown(): void
    {
        Plugin::autoloadModules('');

        $this->restoreServerEnvGetPost();
        $this->resetStaticProperties([Plugin::class]);

        parent::tearDown();
    }

    public function testDependsOnCoreBundlePlugin(): void
    {
        $plugin = new Plugin();

        $this->assertInstanceOf(DependentPluginInterface::class, $plugin);
        $this->assertSame(['contao/core-bundle'], $plugin->getPackageDependencies());
    }

    public function testReturnsTheBundles(): void
    {
        $plugin = new Plugin();
        $bundles = $plugin->getBundles(new DelegatingParser());

        $this->assertCount(13, $bundles);

        $this->assertSame(FrameworkBundle::class, $bundles[0]->getName());
        $this->assertSame([], $bundles[0]->getReplace());
        $this->assertSame([], $bundles[0]->getLoadAfter());

        $this->assertSame(SecurityBundle::class, $bundles[1]->getName());
        $this->assertSame([], $bundles[1]->getReplace());
        $this->assertSame([FrameworkBundle::class], $bundles[1]->getLoadAfter());

        $this->assertSame(TwigBundle::class, $bundles[2]->getName());
        $this->assertSame([], $bundles[2]->getReplace());
        $this->assertSame([], $bundles[2]->getLoadAfter());

        $this->assertSame(TwigExtraBundle::class, $bundles[3]->getName());
        $this->assertSame([], $bundles[3]->getReplace());
        $this->assertSame([], $bundles[3]->getLoadAfter());

        $this->assertSame(MonologBundle::class, $bundles[4]->getName());
        $this->assertSame([], $bundles[4]->getReplace());
        $this->assertSame([], $bundles[4]->getLoadAfter());

        $this->assertSame(DoctrineBundle::class, $bundles[5]->getName());
        $this->assertSame([], $bundles[5]->getReplace());
        $this->assertSame([], $bundles[5]->getLoadAfter());

        $this->assertSame(NelmioCorsBundle::class, $bundles[6]->getName());
        $this->assertSame([], $bundles[6]->getReplace());
        $this->assertSame([], $bundles[6]->getLoadAfter());

        $this->assertSame(NelmioSecurityBundle::class, $bundles[7]->getName());
        $this->assertSame([], $bundles[7]->getReplace());
        $this->assertSame([], $bundles[7]->getLoadAfter());

        $this->assertSame(FOSHttpCacheBundle::class, $bundles[8]->getName());
        $this->assertSame([], $bundles[8]->getReplace());
        $this->assertSame([], $bundles[8]->getLoadAfter());

        $this->assertSame(ContaoManagerBundle::class, $bundles[9]->getName());
        $this->assertSame([], $bundles[9]->getReplace());
        $this->assertSame([ContaoCoreBundle::class], $bundles[9]->getLoadAfter());

        $this->assertSame(DebugBundle::class, $bundles[10]->getName());
        $this->assertSame([], $bundles[10]->getReplace());
        $this->assertSame([], $bundles[10]->getLoadAfter());
        $this->assertFalse($bundles[10]->loadInProduction());

        $this->assertSame(WebProfilerBundle::class, $bundles[11]->getName());
        $this->assertSame([], $bundles[11]->getReplace());
        $this->assertSame([], $bundles[11]->getLoadAfter());
        $this->assertFalse($bundles[11]->loadInProduction());

        $this->assertSame(FlysystemBundle::class, $bundles[12]->getName());
        $this->assertSame([], $bundles[12]->getReplace());
        $this->assertSame([ContaoCoreBundle::class], $bundles[12]->getLoadAfter());
        $this->assertTrue($bundles[12]->loadInProduction());
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
            ->willReturnCallback(static fn ($resource): array => [$resource])
        ;

        $plugin = new Plugin();
        $configs = $plugin->getBundles($parser);

        $this->assertCount(15, $configs);
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
                },
            )
        ;

        $plugin = new Plugin();
        $plugin->registerContainerConfiguration($loader, []);

        $this->assertContains('config_prod.yaml', $files);
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
                },
            )
        ;

        $plugin = new Plugin();
        $plugin->registerContainerConfiguration($loader, []);

        $this->assertContains('config_dev.yaml', $files);
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
                },
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
        $routes = array_values($collection->all());

        $this->assertCount(2, $routes);
        $this->assertSame('/_wdt/foobar', $routes[0]->getPath());
        $this->assertSame('/_profiler/foobar', $routes[1]->getPath());
    }

    public function testReturnsApiCommands(): void
    {
        $files = Finder::create()
            ->name('*.php')
            ->in(__DIR__.'/../../src/ContaoManager/ApiCommand')
        ;

        foreach ($files as $file) {
            $this->assertContains(
                'Contao\ManagerBundle\ContaoManager\ApiCommand\\'.$file->getBasename('.php'),
                (new Plugin())->getApiCommands(),
            );
        }
    }

    public function testReturnsApiFeatures(): void
    {
        $this->assertSame(
            [
                'dot-env' => [
                    'APP_SECRET',
                    'APP_ENV',
                    'COOKIE_WHITELIST',
                    'DATABASE_URL',
                    'DISABLE_HTTP_CACHE',
                    'MAILER_DSN',
                    'TRACE_LEVEL',
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
            (new Plugin())->getApiFeatures(),
        );
    }

    public function testSetsTheAppSecret(): void
    {
        $container = $this->getContainer();

        (new Plugin())->getExtensionConfig('framework', [], $container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame('ThisTokenIsNotSoSecretChangeIt', $bag['env(APP_SECRET)']);
    }

    public function testSetsDnsMappingParameterAndFallback(): void
    {
        $container = $this->getContainer();

        (new Plugin())->getExtensionConfig('contao', [], $container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame('[]', $bag['env(DNS_MAPPING)']);
        $this->assertSame('%env(json:DNS_MAPPING)%', $bag['contao.dns_mapping']);
    }

    public function testDoesNotSetDnsParameterIfAlreadyDefined(): void
    {
        $container = $this->getContainer();
        $container->setParameter('contao.dns_mapping', ['example.com' => 'example.local']);

        (new Plugin())->getExtensionConfig('framework', [], $container);

        $bag = $container->getParameterBag()->all();

        $this->assertFalse(isset($bag['env(DNS_MAPPING)']));
        $this->assertSame(['example.com' => 'example.local'], $bag['contao.dns_mapping']);
    }

    /**
     * @dataProvider getDatabaseParameters
     */
    public function testSetsTheDatabaseUrl(string|null $user, string|null $password, string|null $name, string $expected): void
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
            'pdo-mysql://localhost:3306',
        ];

        yield [
            null,
            null,
            'contao_test',
            'pdo-mysql://localhost:3306/contao_test',
        ];

        yield [
            null,
            'foobar',
            'contao_test',
            'pdo-mysql://localhost:3306/contao_test',
        ];

        yield [
            'root',
            null,
            'contao_test',
            'pdo-mysql://root@localhost:3306/contao_test',
        ];

        yield [
            'root',
            'foobar',
            'contao_test',
            'pdo-mysql://root:foobar@localhost:3306/contao_test',
        ];

        yield [
            'root',
            'aA&3yuA?123-2ABC',
            'contao_test',
            'pdo-mysql://root:aA%%263yuA%%3F123-2ABC@localhost:3306/contao_test',
        ];
    }

    public function testSetsTheDatabaseDriverUrl(): void
    {
        $container = $this->getContainer();
        $container->setParameter('database_user', 'root');
        $container->setParameter('database_password', 'foobar');
        $container->setParameter('database_name', 'contao_test');

        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'mysqli',
                        ],
                    ],
                ],
            ],
        ];

        (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame('mysqli://root:foobar@localhost:3306/contao_test', $bag['env(DATABASE_URL)']);
    }

    public function testAddsThePdoOptions(): void
    {
        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'pdo_mysql',
                            'options' => [
                                1002 => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expect = [
            ...$extensionConfigs,
            ...[[
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'options' => [
                                \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $this->assertSame($expect, $extensionConfig);
    }

    public function testDoesNotAddDefaultPdoOptionsIfDriverIsNotPdo(): void
    {
        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'mysqli',
                            'host' => 'localhost',
                            'options' => [
                                3 => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $this->assertSame($extensionConfigs, $extensionConfig);
    }

    public function testDoesNotAddDefaultPdoOptionsIfUrlIsMysqli(): void
    {
        $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = 'mysqli://root:%%40foobar@localhost:3306/database';

        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'url' => '%env(DATABASE_URL)%',
                            'options' => [
                                3 => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Adjust the error_reporting to suppress mysqli warnings
        $er = error_reporting();
        error_reporting($er ^ E_WARNING ^ E_DEPRECATED);

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        error_reporting($er);

        $this->assertSame($extensionConfigs, $extensionConfig);
    }

    public function testDoesNotOverrideThePdoMultiStatementsOption(): void
    {
        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'driver' => 'pdo_mysql',
                            'options' => [
                                \PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
                                1002 => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $this->assertSame($extensionConfigs, $extensionConfig);
    }

    /**
     * @dataProvider provideDatabaseDrivers
     */
    public function testEnablesStrictMode(array $connectionConfig, int $expectedOptionKey): void
    {
        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => $connectionConfig,
                    ],
                ],
            ],
        ];

        $expect = [
            ...$extensionConfigs,
            ...[[
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'options' => [
                                $expectedOptionKey => "SET SESSION sql_mode=CONCAT(@@sql_mode, IF(INSTR(@@sql_mode, 'STRICT_'), '', ',TRADITIONAL'))",
                            ],
                        ],
                    ],
                ],
            ]],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $this->assertSame($expect, $extensionConfig);
    }

    public function provideDatabaseDrivers(): \Generator
    {
        yield 'pdo with driver' => [
            [
                'driver' => 'mysql',
                'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
            ],
            1002,
        ];

        yield 'pdo with driver alias mysql2' => [
            [
                'driver' => 'mysql2',
                'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
            ],
            1002,
        ];

        yield 'pdo with driver alias pdo_mysql' => [
            [
                'driver' => 'pdo_mysql',
                'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
            ],
            1002,
        ];

        yield 'pdo with url' => [
            [
                'url' => 'mysql://user:secret@localhost/mydb',
                'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
            ],
            1002,
        ];

        yield 'pdo with url and driver alias mysql2' => [
            [
                'url' => 'mysql2://user:secret@localhost/mydb',
                'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
            ],
            1002,
        ];

        yield 'pdo with url and driver alias pdo_mysql' => [
            [
                'url' => 'pdo-mysql://user:secret@localhost/mydb',
                'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
            ],
            1002,
        ];

        yield 'mysqli with driver' => [
            [
                'driver' => 'mysqli',
            ],
            3,
        ];

        yield 'mysqli with url' => [
            [
                'url' => 'mysqli://user:secret@localhost/mydb',
            ],
            3,
        ];
    }

    /**
     * @dataProvider provideUserExtensionConfigs
     */
    public function testSetsDefaultCollation(array $userExtensionConfig): void
    {
        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'options' => [\PDO::MYSQL_ATTR_MULTI_STATEMENTS => false],
                            'default_table_options' => [
                                'charset' => 'utf8mb4',
                                'collate' => 'utf8mb4_unicode_ci',
                                'collation' => 'utf8mb4_unicode_ci',
                            ],
                        ],
                    ],
                ],
            ],
            $userExtensionConfig,
        ];

        $expect = [
            ...$extensionConfigs,
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'default_table_options' => [
                                'collate' => 'utf8_unicode_ci',
                                'collation' => 'utf8_unicode_ci',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('doctrine', $extensionConfigs, $container);

        $this->assertSame($expect, $extensionConfig);
    }

    public function provideUserExtensionConfigs(): \Generator
    {
        yield 'collate' => [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'default_table_options' => [
                                'charset' => 'utf8',
                                'collate' => 'utf8_unicode_ci',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        yield 'collation' => [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'default_table_options' => [
                                'charset' => 'utf8',
                                'collation' => 'utf8_unicode_ci',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testUpdatesTheMailerTransport(): void
    {
        $container = $this->getContainer();
        $container->setParameter('mailer_transport', 'mail');

        (new Plugin())->getExtensionConfig('framework', [], $container);

        $this->assertSame('sendmail', $container->getParameter('mailer_transport'));
    }

    public function testAddsDefaultMailer(): void
    {
        $expect = [
            [
                'mailer' => [
                    'dsn' => '%env(MAILER_DSN)%',
                ],
            ],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('framework', [], $container);

        $this->assertSame($expect, $extensionConfig);
    }

    public function testDoesNotAddDefaultMailerIfDefined(): void
    {
        $extensionConfigs = [
            [
                'mailer' => [
                    'dsn' => 'smtp://localhost',
                ],
            ],
        ];

        $expect = $extensionConfigs;

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('framework', $extensionConfigs, $container);

        $this->assertSame($expect, $extensionConfig);

        $extensionConfigs = [
            [
                'mailer' => [
                    'transports' => [
                        'default' => 'smtp://localhost',
                    ],
                ],
            ],
        ];

        $expect = $extensionConfigs;

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('framework', $extensionConfigs, $container);

        $this->assertSame($expect, $extensionConfig);
    }

    /**
     * @dataProvider getMailerParameters
     */
    public function testSetsTheMailerDsn(string $transport, string|null $host, string|null $user, string|null $password, int|null $port, string|null $encryption, string $expected): void
    {
        $container = $this->getContainer();
        $container->setParameter('mailer_transport', $transport);
        $container->setParameter('mailer_host', $host);
        $container->setParameter('mailer_user', $user);
        $container->setParameter('mailer_password', $password);
        $container->setParameter('mailer_port', $port);
        $container->setParameter('mailer_encryption', $encryption);

        (new Plugin())->getExtensionConfig('framework', [], $container);

        $bag = $container->getParameterBag()->all();

        $this->assertSame($expected, $bag['env(MAILER_DSN)']);
    }

    public function getMailerParameters(): \Generator
    {
        $default = 'sendmail://default';

        if (class_exists(NativeTransportFactory::class)) {
            $default = 'native://default';
        }

        yield [
            'mail',
            null,
            null,
            null,
            null,
            null,
            $default,
        ];

        yield [
            'sendmail',
            '127.0.0.1',
            null,
            null,
            25,
            null,
            $default,
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
            'smtp://foo%%40bar.com@127.0.0.1:25',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            25,
            null,
            'smtp://foo%%40bar.com:foobar@127.0.0.1:25',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            null,
            'ssl',
            'smtps://foo%%40bar.com:foobar@127.0.0.1',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            465,
            'ssl',
            'smtps://foo%%40bar.com:foobar@127.0.0.1:465',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            null,
            null,
            587,
            'tls',
            'smtp://127.0.0.1:587',
        ];

        yield [
            'smtp',
            '127.0.0.1',
            'foo@bar.com',
            'foobar',
            587,
            'tls',
            'smtp://foo%%40bar.com:foobar@127.0.0.1:587',
        ];
    }

    public function testUpdatesTheClickjackingPaths(): void
    {
        $extensionConfigs = [
            [
                'clickjacking' => [
                    'paths' => [
                        '^/foobar/' => 'ALLOW',
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('nelmio_security', $extensionConfigs, $container);

        $expectedConfigs = [
            [
                'clickjacking' => [
                    'paths' => [
                        '^/foobar/' => 'ALLOW',
                    ],
                ],
            ],
            [
                'clickjacking' => [
                    'paths' => [
                        '^/.*' => 'SAMEORIGIN',
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedConfigs, $extensionConfig);
    }

    public function testDoesNotOverrideDefaultClickjackingPath(): void
    {
        $extensionConfigs = [
            [
                'clickjacking' => [
                    'paths' => [
                        '^/foobar/' => 'DENY',
                        '^/.*' => 'ALLOW',
                    ],
                ],
            ],
        ];

        $container = $this->getContainer();
        $extensionConfig = (new Plugin())->getExtensionConfig('nelmio_security', $extensionConfigs, $container);

        $this->assertSame($extensionConfigs, $extensionConfig);
    }

    public function testDoesNotAddDefaultDoctrineMappingIfEntityFolderDoesNotExists(): void
    {
        $plugin = new Plugin();
        $extensionConfig = $plugin->getExtensionConfig('doctrine', [], $this->getContainer());

        // Ignore the DBAL entry
        unset($extensionConfig[0]['dbal']);

        $this->assertCount(1, $extensionConfig);
        $this->assertEmpty($extensionConfig[0]);
    }

    /**
     * @dataProvider getOrmMappingConfigurations
     */
    public function testOnlyAddsTheDefaultDoctrineMappingIfAutoMappingIsEnabledAndNotAlreadyConfigured(array $ormConfig, string $defaultEntityManager, bool $shouldAdd): void
    {
        $extensionConfigs = [
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'url' => '%env(DATABASE_URL)%',
                            'password' => '@foobar',
                        ],
                    ],
                ],
                'orm' => $ormConfig,
            ],
            [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'options' => [
                                \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                                1002 => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expect = $extensionConfigs;

        if ($shouldAdd) {
            $expect = [
                ...$extensionConfigs,
                [
                    'orm' => [
                        'entity_managers' => [
                            $defaultEntityManager => [
                                'mappings' => [
                                    'App' => [
                                        'dir' => '%kernel.project_dir%/src/Entity',
                                        'is_bundle' => false,
                                        'prefix' => 'App\Entity',
                                        'alias' => 'App',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $plugin = new Plugin();

        $container = $this->getContainer();
        $container->setParameter('kernel.project_dir', __DIR__.'/../Fixtures/app-with-entities');

        $this->assertSame($expect, $plugin->getExtensionConfig('doctrine', $extensionConfigs, $container));
    }

    public function getOrmMappingConfigurations(): \Generator
    {
        // Positive configurations
        yield 'with global auto_mapping enabled' => [
            [
                'auto_mapping' => true,
            ],
            'default',
            true,
        ];

        yield 'with auto_mapping enabled in default entity manager' => [
            [
                'entity_managers' => [
                    'default' => [
                        'auto_mapping' => true,
                    ],
                ],
            ],
            'default',
            true,
        ];

        yield 'with auto_mapping enabled in a renamed default entity manager' => [
            [
                'default_entity_manager' => 'foo',
                'entity_managers' => [
                    'foo' => [
                        'auto_mapping' => true,
                    ],
                ],
            ],
            'foo',
            true,
        ];

        // Skip, because auto_mapping is not set
        yield 'with auto_mapping not set' => [
            [
            ],
            'default',
            false,
        ];

        yield 'with global auto_mapping disabled' => [
            [
                'auto_mapping' => false,
            ],
            'default',
            false,
        ];

        yield 'with auto_mapping disabled in default entity manager' => [
            [
                'entity_managers' => [
                    'default' => [
                        'auto_mapping' => false,
                    ],
                ],
            ],
            'default',
            false,
        ];

        yield 'with auto_mapping disabled in a renamed default entity manager' => [
            [
                'default_entity_manager' => 'foo',
                'entity_managers' => [
                    'foo' => [
                        'auto_mapping' => false,
                    ],
                ],
            ],
            'foo',
            false,
        ];

        // Skip, because conflicting mapping already exists (global)
        yield 'with existing global mapping "App"' => [
            [
                'auto_mapping' => true,
                'mappings' => [
                    'App' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
            'default',
            false,
        ];

        yield 'with existing global mapping with alias "App"' => [
            [
                'auto_mapping' => true,
                'mappings' => [
                    'Foo' => [
                        'alias' => 'App',
                    ],
                ],
            ],
            'default',
            false,
        ];

        yield 'with existing global mapping setting target directory' => [
            [
                'auto_mapping' => true,
                'mappings' => [
                    'Foo' => [
                        'dir' => '%kernel.project_dir%/src/Entity',
                    ],
                ],
            ],
            'default',
            false,
        ];

        // Skip, because conflicting mapping already exists (in any entity manager)
        yield 'with existing mapping "App" in any entity manager' => [
            [
                'auto_mapping' => true,
                'entity_managers' => [
                    'foo' => [
                        'mappings' => [
                            'App' => [
                                'foo' => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
            'default',
            false,
        ];

        yield 'with existing mapping with alias "App" in any entity manager' => [
            [
                'auto_mapping' => true,
                'entity_managers' => [
                    'foo' => [
                        'mappings' => [
                            'bar' => [
                                'alias' => 'App',
                            ],
                        ],
                    ],
                ],
            ],
            'default',
            false,
        ];

        yield 'with existing mapping setting target directory in any entity manager' => [
            [
                'auto_mapping' => true,
                'entity_managers' => [
                    'foo' => [
                        'mappings' => [
                            'bar' => [
                                'dir' => '%kernel.project_dir%/src/Entity',
                            ],
                        ],
                    ],
                ],
            ],
            'default',
            false,
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
        $container->setParameter('kernel.project_dir', __DIR__.'/../Fixtures/app');

        return $container;
    }
}
