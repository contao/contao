<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection;

use Contao\CoreBundle\Cron\CronJob;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Contao\CoreBundle\DependencyInjection\Attribute\AsPickerProvider;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Contao\CoreBundle\Doctrine\Backup\RetentionPolicy;
use Contao\CoreBundle\EventListener\CsrfTokenCookieSubscriber;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Fragment\Reference\FrontendModuleReference;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Contao\CoreBundle\Tests\Fixtures\ClassWithMethod;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\Security\Core\Authorization\TraceableAccessDecisionManager;
use Symfony\Component\Security\Http\Firewall;

class ContaoCoreExtensionTest extends TestCase
{
    use ExpectDeprecationTrait;

    public function testValidatesTheSymfonyListenerPriorities(): void
    {
        $events = AbstractSessionListener::getSubscribedEvents();

        $this->assertSame('onKernelResponse', $events['kernel.response'][0]);
        $this->assertSame(-1000, $events['kernel.response'][1]);

        $events = BaseLocaleListener::getSubscribedEvents();

        $this->assertSame('onKernelRequest', $events['kernel.request'][1][0]);
        $this->assertSame(16, $events['kernel.request'][1][1]);

        $events = ErrorListener::getSubscribedEvents();

        $this->assertSame('onKernelException', $events['kernel.exception'][1][0]);
        $this->assertSame(-128, $events['kernel.exception'][1][1]);

        $events = Firewall::getSubscribedEvents();

        $this->assertSame('onKernelRequest', $events['kernel.request'][0]);
        $this->assertSame(8, $events['kernel.request'][1]);

        $events = RouterListener::getSubscribedEvents();

        $this->assertSame('onKernelRequest', $events['kernel.request'][0][0]);
        $this->assertSame(32, $events['kernel.request'][0][1]);
    }

    public function testRegistersTheMakeResponsePrivateListenerAtTheEnd(): void
    {
        $container = $this->getContainerBuilder();

        $makeResponsePrivateDefinition = $container->getDefinition('contao.listener.make_response_private');
        $makeResponsePrivateTags = $makeResponsePrivateDefinition->getTags();
        $makeResponsePrivatePriority = $makeResponsePrivateTags['kernel.event_listener'][0]['priority'] ?? 0;

        $mergeHeadersListenerDefinition = $container->getDefinition('contao.listener.merge_http_headers');
        $mergeHeadersListenerTags = $mergeHeadersListenerDefinition->getTags();
        $mergeHeadersListenerPriority = $mergeHeadersListenerTags['kernel.event_listener'][0]['priority'] ?? 0;

        // Ensure that the listener is registered after the MergeHeaderListener
        $this->assertTrue($makeResponsePrivatePriority < $mergeHeadersListenerPriority);

        $clearSessionDataListenerDefinition = $container->getDefinition('contao.listener.clear_session_data');
        $clearSessionDataListenerTags = $clearSessionDataListenerDefinition->getTags();
        $clearSessionDataListenerPriority = $clearSessionDataListenerTags['kernel.event_listener'][0]['priority'] ?? 0;

        // Ensure that the listener is registered after the ClearSessionDataListener
        $this->assertTrue($makeResponsePrivatePriority < $clearSessionDataListenerPriority);

        $csrfCookieListenerPriority = CsrfTokenCookieSubscriber::getSubscribedEvents()['kernel.response'][1] ?? 0;

        // Ensure that the listener is registered after the CsrfTokenCookieSubscriber
        $this->assertTrue($makeResponsePrivatePriority < (int) $csrfCookieListenerPriority);
    }

    public function testRegistersTheSecurityTokenCheckerWithRoleHierarchyVoter(): void
    {
        $container = $this->getContainerBuilder();

        // Populate security configuration
        $container->setParameter('security.role_hierarchy.roles', [
            'ROLE_ADMIN' => ['ROLE_USER'],
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
        ]);

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $definition = $container->getDefinition('contao.security.token_checker');

        $this->assertEquals(new Reference('security.access.role_hierarchy_voter'), $definition->getArgument(4));
    }

    public function testRegistersThePredefinedImageSizes(): void
    {
        $container = $this->getContainerBuilder();

        $services = ['contao.image.sizes', 'contao.image.factory', 'contao.image.picture_factory'];

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        foreach ($services as $service) {
            $this->assertFalse($container->getDefinition($service)->hasMethodCall('setPredefinedSizes'));
        }

        $extension->load(
            [
                'contao' => [
                    'image' => [
                        'sizes' => [
                            '_defaults' => [
                                'width' => 150,
                                'formats' => [
                                    'jpg' => ['webp', 'jpg'],
                                ],
                            ],
                            'foo' => [
                                'height' => 250,
                            ],
                            'bar' => [
                            ],
                            'foobar' => [
                                'width' => 100,
                                'height' => 200,
                                'resize_mode' => 'box',
                                'zoom' => 100,
                                'css_class' => 'foobar-image',
                                'lazy_loading' => true,
                                'densities' => '1x, 2x',
                                'sizes' => '100vw',
                                'skip_if_dimensions_match' => false,
                                'items' => [[
                                    'width' => 50,
                                    'height' => 50,
                                    'resize_mode' => 'box',
                                    'zoom' => 100,
                                    'densities' => '0.5x, 2x',
                                    'sizes' => '50vw',
                                    'media' => '(max-width: 900px)',
                                ]],
                            ],
                        ],
                    ],
                ],
            ],
            $container,
        );

        foreach ($services as $service) {
            $this->assertTrue($container->getDefinition($service)->hasMethodCall('setPredefinedSizes'));
        }

        $methodCalls = $container->getDefinition('contao.image.sizes')->getMethodCalls();

        $this->assertSame('setPredefinedSizes', $methodCalls[0][0]);

        $expectedSizes = [[
            '_foo' => [
                'width' => 150,
                'height' => 250,
                'items' => [],
                'preserveMetadataFields' => [],
                'formats' => [
                    'jpg' => ['webp', 'jpg'],
                ],
            ],
            '_bar' => [
                'width' => 150,
                'items' => [],
                'preserveMetadataFields' => [],
                'formats' => [
                    'jpg' => ['webp', 'jpg'],
                ],
            ],
            '_foobar' => [
                'width' => 100,
                'height' => 200,
                'resizeMode' => 'box',
                'zoom' => 100,
                'cssClass' => 'foobar-image',
                'lazyLoading' => true,
                'densities' => '1x, 2x',
                'sizes' => '100vw',
                'skipIfDimensionsMatch' => false,
                'items' => [[
                    'width' => 50,
                    'height' => 50,
                    'resizeMode' => 'box',
                    'zoom' => 100,
                    'densities' => '0.5x, 2x',
                    'sizes' => '50vw',
                    'media' => '(max-width: 900px)',
                ]],
                'preserveMetadataFields' => [],
                'formats' => [
                    'jpg' => ['webp', 'jpg'],
                ],
            ],
        ]];

        $sizes = $methodCalls[0][1];

        $sortByKeyRecursive = static function (array &$array) use (&$sortByKeyRecursive): void {
            foreach ($array as &$value) {
                if (\is_array($value)) {
                    $sortByKeyRecursive($value);
                }
            }

            unset($value);
            ksort($array);
        };

        $sortByKeyRecursive($expectedSizes);
        $sortByKeyRecursive($sizes);

        $this->assertSame($expectedSizes, $sizes);
    }

    public function testSetsTheCrawlOptionsOnTheEscargotFactory(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load(
            [
                'contao' => [
                    'crawl' => [
                        'additional_uris' => [
                            'https://example.com',
                        ],
                        'default_http_client_options' => [
                            'proxy' => 'http://localhost:7080',
                            'headers' => [
                                'Foo' => 'Bar',
                            ],
                        ],
                    ],
                ],
            ],
            $container,
        );

        $definition = $container->getDefinition('contao.crawl.escargot.factory');

        $this->assertSame(['https://example.com'], $definition->getArgument(2));
        $this->assertSame(['proxy' => 'http://localhost:7080', 'headers' => ['Foo' => 'Bar']], $definition->getArgument(3));
    }

    public function testConfiguresTheBackupManagerCorrectly(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $definition = $container->getDefinition('contao.doctrine.backup_manager');

        $this->assertEquals(new Reference('database_connection'), $definition->getArgument(0));
        $this->assertEquals(new Reference('contao.doctrine.backup.dumper'), $definition->getArgument(1));
        $this->assertEquals(new Reference('contao.filesystem.virtual.backups'), $definition->getArgument(2));
        $this->assertSame(['tl_crawl_queue', 'tl_log', 'tl_search', 'tl_search_index', 'tl_search_term'], $definition->getArgument(3));
        $this->assertEquals(new Reference('contao.doctrine.backup.retention_policy'), $definition->getArgument(4));

        $retentionPolicyDefinition = $container->getDefinition('contao.doctrine.backup.retention_policy');

        $this->assertSame(RetentionPolicy::class, $retentionPolicyDefinition->getClass());
        $this->assertSame(5, $retentionPolicyDefinition->getArgument(0));
        $this->assertSame(['1D', '7D', '14D', '1M'], $retentionPolicyDefinition->getArgument(1));

        $extension->load(
            [
                'contao' => [
                    'backup' => [
                        'ignore_tables' => ['foobar'],
                        'keep_max' => 10,
                        'keep_intervals' => ['1D', '2D', '7D', '14D', '1M', '1Y'],
                    ],
                ],
            ],
            $container,
        );

        $definition = $container->getDefinition('contao.doctrine.backup_manager');

        $this->assertEquals(new Reference('database_connection'), $definition->getArgument(0));
        $this->assertEquals(new Reference('contao.doctrine.backup.dumper'), $definition->getArgument(1));
        $this->assertEquals(new Reference('contao.filesystem.virtual.backups'), $definition->getArgument(2));
        $this->assertSame(['foobar'], $definition->getArgument(3));
        $this->assertEquals(new Reference('contao.doctrine.backup.retention_policy'), $definition->getArgument(4));

        $retentionPolicyDefinition = $container->getDefinition('contao.doctrine.backup.retention_policy');

        $this->assertSame(RetentionPolicy::class, $retentionPolicyDefinition->getClass());
        $this->assertSame(10, $retentionPolicyDefinition->getArgument(0));
        $this->assertSame(['1D', '2D', '7D', '14D', '1M', '1Y'], $retentionPolicyDefinition->getArgument(1));
    }

    public function testConfiguresCronSchedulerCorrectly(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load(
            [
                'contao' => [
                    'cron' => [
                        'web_listener' => false,
                    ],
                ],
            ],
            $container,
        );

        // Disabling should remove the definition, no cron job should be configured
        $this->assertFalse($container->hasDefinition('contao.listener.command_scheduler'));
        $this->assertCount(0, $container->findDefinition('contao.cron')->getMethodCalls());

        $extension->load(
            [
                'contao' => [
                    'cron' => [
                        'web_listener' => true,
                    ],
                ],
            ],
            $container,
        );

        // Forcing it to true should disable auto mode, no cron job should be configured
        $definition = $container->findDefinition('contao.listener.command_scheduler');

        $this->assertFalse($definition->getArgument(3));
        $this->assertCount(0, $container->findDefinition('contao.cron')->getMethodCalls());

        $extension->load(
            [
                'contao' => [
                    'cron' => [
                        'web_listener' => 'auto',
                    ],
                ],
            ],
            $container,
        );

        // Auto should also configure the minutely cron job
        $definition = $container->findDefinition('contao.listener.command_scheduler');

        $this->assertTrue($definition->getArgument(3));
        $this->assertCount(1, $container->findDefinition('contao.cron')->getMethodCalls());
        $this->assertSame('addCronJob', $container->findDefinition('contao.cron')->getMethodCalls()[0][0]);

        /** @var Definition $definition */
        $definition = $container->findDefinition('contao.cron')->getMethodCalls()[0][1][0];

        $this->assertSame(CronJob::class, $definition->getClass());
        $this->assertSame('contao.cron', (string) $definition->getArgument(0));
        $this->assertSame('* * * * *', $definition->getArgument(1));
        $this->assertSame('updateMinutelyCliCron', $definition->getArgument(2));
    }

    public function testRegistersTheDefaultSearchIndexer(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load(
            [
                'contao' => [
                    'search' => [
                        'default_indexer' => [
                            'enable' => true,
                        ],
                        'index_protected' => true,
                    ],
                ],
            ],
            $container,
        );

        $this->assertArrayHasKey(IndexerInterface::class, $container->getAutoconfiguredInstanceof());
        $this->assertTrue($container->hasDefinition('contao.search.default_indexer'));

        $definition = $container->getDefinition('contao.search.default_indexer');

        $this->assertTrue($definition->getArgument(2));
    }

    public function testDoesNotRegisterTheDefaultSearchIndexerIfItIsDisabled(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load(
            [
                'contao' => [
                    'search' => [
                        'default_indexer' => [
                            'enable' => false,
                        ],
                    ],
                ],
            ],
            $container,
        );

        // Should still have the interface registered for autoconfiguration
        $this->assertArrayHasKey(IndexerInterface::class, $container->getAutoconfiguredInstanceof());
        $this->assertFalse($container->hasDefinition('contao.search.default_indexer'));
    }

    public function testSetsTheCorrectFeatureFlagOnTheSearchIndexListener(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load(
            [
                'contao' => [
                    'search' => [
                        'listener' => [
                            'delete' => false,
                        ],
                    ],
                ],
            ],
            $container,
        );

        $definition = $container->getDefinition('contao.listener.search_index');

        $this->assertSame(SearchIndexListener::class, $definition->getClass());
        $this->assertSame(SearchIndexListener::FEATURE_INDEX, $definition->getArgument(2));
    }

    public function testRemovesTheSearchIndexListenerIfItIsDisabled(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load(
            [
                'contao' => [
                    'search' => [
                        'listener' => [
                            'index' => false,
                            'delete' => false,
                        ],
                    ],
                ],
            ],
            $container,
        );

        $this->assertFalse($container->has('contao.listener.search_index'));
    }

    public function testRegistersTheImageTargetPath(): void
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.charset' => 'UTF-8',
                'kernel.project_dir' => Path::normalize($this->getTempDir()),
                'kernel.default_locale' => 'en',
            ]),
        );

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $this->assertSame(Path::normalize($this->getTempDir()).'/assets/images', $container->getParameter('contao.image.target_dir'));
    }

    /**
     * @dataProvider provideComposerJsonContent
     */
    public function testSetsTheWebDirFromTheRootComposerJson(array $composerJson, string $expectedWebDir): void
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.project_dir' => Path::normalize($this->getTempDir()),
                'kernel.charset' => 'UTF-8',
            ]),
        );

        $composerJsonFilePath = Path::join($this->getTempDir(), 'composer.json');

        $filesystem = new Filesystem();
        $filesystem->dumpFile($composerJsonFilePath, json_encode($composerJson, JSON_THROW_ON_ERROR));

        (new ContaoCoreExtension())->load([], $container);

        $filesystem->remove($composerJsonFilePath);

        $this->assertSame(Path::join($this->getTempDir(), $expectedWebDir), $container->getParameter('contao.web_dir'));
    }

    public function provideComposerJsonContent(): \Generator
    {
        yield 'extra.public-dir key not present' => [
            [],
            'public',
        ];

        yield 'extra.public-dir configured' => [
            ['extra' => ['public-dir' => 'foo']],
            'foo',
        ];
    }

    public function testPrependsMonologConfigurationWithActionChannels(): void
    {
        $channels = [
            'contao.access',
            'contao.configuration',
            'contao.cron',
            'contao.email',
            'contao.error',
            'contao.files',
            'contao.forms',
            'contao.general',
        ];

        $monologExtension = $this->createMock(Extension::class);
        $monologExtension
            ->method('getAlias')
            ->willReturn('monolog')
        ;

        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.project_dir' => Path::normalize($this->getTempDir()),
            ]),
        );

        $container->registerExtension($monologExtension);

        $extension = new ContaoCoreExtension();
        $extension->prepend($container);

        $config = $container->getExtensionConfig('monolog');

        $this->assertSame($channels, $config[0]['channels'] ?? []);
    }

    public function testDoesNotPrependMonologConfigurationWithoutMonologExtension(): void
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.project_dir' => Path::normalize($this->getTempDir()),
            ]),
        );

        $extension = new ContaoCoreExtension();
        $extension->prepend($container);

        $config = $container->getExtensionConfig('monolog');

        $this->assertSame([], $config);
    }

    public function testConfiguresFilesystemDefaults(): void
    {
        $container = new ContainerBuilder(new ParameterBag([
            'contao.upload_path' => 'upload/path',
        ]));

        $config = $this->createMock(FilesystemConfiguration::class);
        $config
            ->method('getContainer')
            ->willReturn($container)
        ;

        $config
            ->expects($this->exactly(2))
            ->method('mountLocalAdapter')
            ->withConsecutive(['upload/path', 'upload/path', 'files'], ['var/backups', 'backups', 'backups'])
        ;

        $dbafsDefinition = $this->createMock(Definition::class);
        $dbafsDefinition
            ->expects($this->once())
            ->method('addMethodCall')
            ->with('setDatabasePathPrefix', ['upload/path'])
        ;

        $config
            ->expects($this->once())
            ->method('addDefaultDbafs')
            ->with('files', 'tl_files')
            ->willReturn($dbafsDefinition)
        ;

        (new ContaoCoreExtension())->configureFilesystem($config);
    }

    public function testRegistersTraceableAccessDecisionMangerInDebug(): void
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => true,
                'kernel.charset' => 'UTF-8',
                'kernel.project_dir' => Path::normalize($this->getTempDir()),
            ]),
        );

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('contao.debug.security.access.decision_manager'));

        $definition = $container->findDefinition('contao.debug.security.access.decision_manager');
        $this->assertSame(TraceableAccessDecisionManager::class, $definition->getClass());
        $this->assertSame('security.access.decision_manager', $definition->getDecoratedService()[0]);
    }

    public function testHstsSecurityConfiguration(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);

        $this->assertTrue($container->hasDefinition('contao.listener.transport_security_header'));
        $listener = $container->findDefinition('contao.listener.transport_security_header');
        $this->assertSame(31536000, $listener->getArgument(1));

        (new ContaoCoreExtension())->load(
            [
                'contao' => [
                    'security' => [
                        'hsts' => [
                            'ttl' => 500,
                        ],
                    ],
                ],
            ],
            $container,
        );

        $this->assertTrue($container->hasDefinition('contao.listener.transport_security_header'));
        $listener = $container->findDefinition('contao.listener.transport_security_header');
        $this->assertSame(500, $listener->getArgument(1));

        (new ContaoCoreExtension())->load(
            [
                'contao' => [
                    'security' => [
                        'hsts' => false,
                    ],
                ],
            ],
            $container,
        );

        $this->assertFalse($container->hasDefinition('contao.listener.transport_security_header'));
    }

    public function testRegistersAsContentElementAttribute(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $this->assertArrayHasKey(AsContentElement::class, $autoConfiguredAttributes);

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->once())
            ->method('addTag')
            ->with(
                ContentElementReference::TAG_NAME,
                [
                    'foo' => 'bar',
                    'baz' => 42,
                    'type' => 'content_element/text',
                    'category' => 'miscellaneous',
                    'template' => 'a_template',
                    'method' => 'aMethod',
                    'renderer' => 'inline',
                ],
            )
        ;

        $autoConfiguredAttributes[AsContentElement::class](
            $definition,
            new AsContentElement(...[
                'type' => 'content_element/text',
                'template' => 'a_template',
                'method' => 'aMethod',
                'renderer' => 'inline',
                'foo' => 'bar',
                'baz' => 42,
            ]),
        );
    }

    public function testRegistersAsFrontendModuleAttribute(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $this->assertArrayHasKey(AsFrontendModule::class, $autoConfiguredAttributes);

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->once())
            ->method('addTag')
            ->with(
                FrontendModuleReference::TAG_NAME,
                [
                    'foo' => 'bar',
                    'baz' => 42,
                    'type' => 'frontend_module/navigation',
                    'category' => 'miscellaneous',
                    'template' => 'a_template',
                    'method' => 'aMethod',
                    'renderer' => 'inline',
                ],
            )
        ;

        $autoConfiguredAttributes[AsFrontendModule::class](
            $definition,
            new AsFrontendModule(...[
                'type' => 'frontend_module/navigation',
                'template' => 'a_template',
                'method' => 'aMethod',
                'renderer' => 'inline',
                'foo' => 'bar',
                'baz' => 42,
            ]),
        );
    }

    public function testRegistersAsPageAttribute(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $this->assertArrayHasKey(AsPage::class, $autoConfiguredAttributes);

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->once())
            ->method('addTag')
            ->with(
                'contao.page',
                [
                    'type' => 'foo',
                    'path' => '{some}/path',
                    'requirements' => ['some' => '\d'],
                    'options' => ['utf8' => true],
                    'defaults' => [
                        '_scope' => 'backend',
                        '_locale' => 'en',
                        '_format' => 'json',
                    ],
                    'methods' => ['GET'],
                    'contentComposition' => true,
                    'urlSuffix' => 'html',
                ],
            )
        ;

        $autoConfiguredAttributes[AsPage::class](
            $definition,
            new AsPage(
                'foo',
                '{some}/path',
                ['some' => '\d'],
                ['utf8' => true],
                ['_scope' => 'backend'],
                ['GET'],
                'en',
                'json',
                true,
                'html',
            ),
            new \ReflectionClass(ClassWithMethod::class),
        );
    }

    public function testRegistersAsPickerProviderAttribute(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $this->assertArrayHasKey(AsPickerProvider::class, $autoConfiguredAttributes);

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->once())
            ->method('addTag')
            ->with('contao.picker_provider', ['priority' => 32])
        ;

        $autoConfiguredAttributes[AsPickerProvider::class](
            $definition,
            new AsPickerProvider(32),
            new \ReflectionClass(ClassWithMethod::class),
        );
    }

    public function testRegistersAsCronjobAttribute(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $this->assertArrayHasKey(AsCronJob::class, $autoConfiguredAttributes);

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->exactly(2))
            ->method('addTag')
            ->with('contao.cronjob', ['interval' => 'daily', 'method' => 'someMethod'])
        ;

        $autoConfiguredAttributes[AsCronJob::class](
            $definition,
            new AsCronJob('daily', 'someMethod'),
            new \ReflectionClass(ClassWithMethod::class),
        );

        $autoConfiguredAttributes[AsCronJob::class](
            $definition,
            new AsCronJob('daily'),
            (new \ReflectionClass(ClassWithMethod::class))->getMethod('someMethod'),
        );
    }

    public function testRegistersAsHookAttribute(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $this->assertArrayHasKey(AsHook::class, $autoConfiguredAttributes);

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->exactly(2))
            ->method('addTag')
            ->with('contao.hook', ['hook' => 'activateAccount', 'priority' => 32, 'method' => 'someMethod'])
        ;

        $autoConfiguredAttributes[AsHook::class](
            $definition,
            new AsHook('activateAccount', 'someMethod', 32),
            new \ReflectionClass(ClassWithMethod::class),
        );

        $autoConfiguredAttributes[AsHook::class](
            $definition,
            new AsHook('activateAccount', null, 32),
            (new \ReflectionClass(ClassWithMethod::class))->getMethod('someMethod'),
        );
    }

    public function testRegistersAsCallbackAttribute(): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $this->assertArrayHasKey(AsCallback::class, $autoConfiguredAttributes);

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->exactly(2))
            ->method('addTag')
            ->with(
                'contao.callback',
                [
                    'table' => 'tl_foo',
                    'target' => 'list.label.label',
                    'priority' => 32,
                    'method' => 'someMethod',
                ],
            )
        ;

        $autoConfiguredAttributes[AsCallback::class](
            $definition,
            new AsCallback('tl_foo', 'list.label.label', 'someMethod', 32),
            new \ReflectionClass(ClassWithMethod::class),
        );

        $autoConfiguredAttributes[AsCallback::class](
            $definition,
            new AsCallback('tl_foo', 'list.label.label', null, 32),
            (new \ReflectionClass(ClassWithMethod::class))->getMethod('someMethod'),
        );
    }

    /**
     * @dataProvider provideAttributesForMethods
     */
    public function testThrowsExceptionWhenTryingToDeclareTheMethodPropertyOnAMethodAttribute(string $attributeClass): void
    {
        $container = $this->getContainerBuilder();
        (new ContaoCoreExtension())->load([], $container);
        $autoConfiguredAttributes = $container->getAutoconfiguredAttributes();

        $definition = $this->createMock(ChildDefinition::class);
        $definition
            ->expects($this->never())
            ->method('addTag')
        ;

        $attribute = new \stdClass();
        $attribute->method = 'someMethod';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($attributeClass.' attribute cannot declare a method on "Contao\CoreBundle\Tests\Fixtures\ClassWithMethod::someMethod()".');

        $autoConfiguredAttributes[$attributeClass](
            $definition,
            $attribute,
            (new \ReflectionClass(ClassWithMethod::class))->getMethod('someMethod'),
        );
    }

    public function provideAttributesForMethods(): \Generator
    {
        yield 'cronjob' => [AsCronJob::class];
        yield 'hook' => [AsHook::class];
        yield 'callback' => [AsCallback::class];
    }

    private function getContainerBuilder(array|null $params = null): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.charset' => 'UTF-8',
                'kernel.project_dir' => $this->getTempDir(),
                'kernel.default_locale' => 'en',
            ]),
        );

        $params ??= [
            'contao' => [
                'localconfig' => ['foo' => 'bar'],
            ],
        ];

        $extension = new ContaoCoreExtension();
        $extension->load($params, $container);

        return $container;
    }
}
