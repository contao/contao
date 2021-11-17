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

use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\EventListener\CsrfTokenCookieSubscriber;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\DependencyInjection\Compiler\ResolvePrivatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
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

    /**
     * @group legacy
     */
    public function testOnlyRegistersTheRoutingLegacyRouteProviderInLegacyMode(): void
    {
        $this->expectDeprecation('%sURL suffix is configured per root page %s this option requires legacy routing%s');

        $container = $this->getContainerBuilder([
            'contao' => [
                'encryption_key' => 'foobar',
                'localconfig' => ['foo' => 'bar'],
                'legacy_routing' => false,
            ],
        ]);

        $this->assertFalse($container->has('contao.routing.legacy_route_provider'));

        $container = $this->getContainerBuilder();

        $this->assertTrue($container->has('contao.routing.legacy_route_provider'));

        $container = $this->getContainerBuilder([
            'contao' => [
                'encryption_key' => 'foobar',
                'localconfig' => ['foo' => 'bar'],
                'url_suffix' => '.php',
            ],
        ]);

        $this->assertTrue($container->has('contao.routing.legacy_route_provider'));
    }

    /**
     * @group legacy
     */
    public function testOnlyRegistersTheRoutingUrlGeneratorInLegacyMode(): void
    {
        $this->expectDeprecation('%sURL suffix is configured per root page %s this option requires legacy routing%s');

        $container = $this->getContainerBuilder([
            'contao' => [
                'encryption_key' => 'foobar',
                'localconfig' => ['foo' => 'bar'],
                'legacy_routing' => false,
            ],
        ]);

        $this->assertFalse($container->has('contao.routing.url_generator'));

        $container = $this->getContainerBuilder();

        $this->assertTrue($container->has('contao.routing.url_generator'));

        $container = $this->getContainerBuilder([
            'contao' => [
                'encryption_key' => 'foobar',
                'localconfig' => ['foo' => 'bar'],
                'url_suffix' => '.php',
            ],
        ]);

        $this->assertTrue($container->has('contao.routing.url_generator'));
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

        $this->assertEquals(new Reference('security.access.role_hierarchy_voter'), $definition->getArgument(5));
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
            $container
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
                'formats' => [
                    'jpg' => ['webp', 'jpg'],
                ],
            ],
            '_bar' => [
                'width' => 150,
                'items' => [],
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
                        ],
                    ],
                ],
            ],
            $container
        );

        $definition = $container->getDefinition('contao.crawl.escargot.factory');

        $this->assertSame(['https://example.com'], $definition->getArgument(2));
        $this->assertSame(['proxy' => 'http://localhost:7080'], $definition->getArgument(3));
    }

    public function testConfiguresTheBackupManagerCorrectly(): void
    {
        $container = $this->getContainerBuilder();

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $definition = $container->getDefinition('contao.doctrine.backup_manager');

        $this->assertEquals(new Reference('database_connection'), $definition->getArgument(0));
        $this->assertEquals(new Reference('contao.doctrine.backup.dumper'), $definition->getArgument(1));
        $this->assertSame('%kernel.project_dir%/var/backups', $definition->getArgument(2));
        $this->assertSame(['tl_crawl_queue', 'tl_log', 'tl_search', 'tl_search_index', 'tl_search_term'], $definition->getArgument(3));
        $this->assertSame(5, $definition->getArgument(4));

        $extension->load(
            [
                'contao' => [
                    'backup' => [
                        'directory' => 'somewhere/else',
                        'ignore_tables' => ['foobar'],
                        'keep_max' => 10,
                    ],
                ],
            ],
            $container
        );

        $definition = $container->getDefinition('contao.doctrine.backup_manager');

        $this->assertEquals(new Reference('database_connection'), $definition->getArgument(0));
        $this->assertEquals(new Reference('contao.doctrine.backup.dumper'), $definition->getArgument(1));
        $this->assertSame('somewhere/else', $definition->getArgument(2));
        $this->assertSame(['foobar'], $definition->getArgument(3));
        $this->assertSame(10, $definition->getArgument(4));
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
            $container
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
            $container
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
            $container
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
            $container
        );

        $this->assertFalse($container->has('contao.listener.search_index'));
    }

    /**
     * @group legacy
     */
    public function testRegistersTheImageTargetPath(): void
    {
        $this->expectDeprecation('Since contao/core-bundle 4.4: Using the "contao.image.target_path" parameter has been deprecated %s.');

        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.charset' => 'UTF-8',
                'kernel.project_dir' => Path::normalize($this->getTempDir()),
                'kernel.default_locale' => 'en',
            ])
        );

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $this->assertSame(Path::normalize($this->getTempDir()).'/assets/images', $container->getParameter('contao.image.target_dir'));

        $params = [
            'contao' => [
                'image' => ['target_path' => 'my/custom/dir'],
            ],
        ];

        $extension = new ContaoCoreExtension();
        $extension->load($params, $container);

        $this->assertSame(Path::normalize($this->getTempDir()).'/my/custom/dir', $container->getParameter('contao.image.target_dir'));
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
            ])
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

    private function getContainerBuilder(array $params = null): ContainerBuilder
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.charset' => 'UTF-8',
                'kernel.project_dir' => $this->getTempDir(),
                'kernel.default_locale' => 'en',
            ])
        );

        if (null === $params) {
            $params = [
                'contao' => [
                    'encryption_key' => 'foobar',
                    'localconfig' => ['foo' => 'bar'],
                ],
            ];
        }

        $extension = new ContaoCoreExtension();
        $extension->load($params, $container);

        // To find out whether we need to run the ResolvePrivatesPass, we take
        // a private service and check the isPublic() return value. In Symfony
        // 4.4, it will be "true", whereas in Symfony 5, it will be "false".
        if (true === $container->findDefinition('contao.routing.page_router')->isPublic()) {
            $pass = new ResolvePrivatesPass();
            $pass->process($container);
        }

        return $container;
    }
}
