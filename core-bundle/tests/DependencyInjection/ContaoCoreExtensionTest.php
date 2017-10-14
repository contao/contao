<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection;

use Contao\CoreBundle\ArgumentResolver\ModelResolver;
use Contao\CoreBundle\Cache\ContaoCacheClearer;
use Contao\CoreBundle\Cache\ContaoCacheWarmer;
use Contao\CoreBundle\Command\AutomatorCommand;
use Contao\CoreBundle\Command\FilesyncCommand;
use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\CoreBundle\Command\UserPasswordCommand;
use Contao\CoreBundle\Command\VersionCommand;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Cors\WebsiteRootsConfigProvider;
use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Contao\CoreBundle\EventListener\BypassMaintenanceListener;
use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\EventListener\CsrfTokenCookieListener;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\EventListener\ExceptionConverterListener;
use Contao\CoreBundle\EventListener\HeaderReplay\PageLayoutListener;
use Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener as HeaderReplayUserSessionListener;
use Contao\CoreBundle\EventListener\InsecureInstallationListener;
use Contao\CoreBundle\EventListener\LocaleListener;
use Contao\CoreBundle\EventListener\MapFragmentsToGlobalsListener;
use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\EventListener\PrettyErrorScreenListener;
use Contao\CoreBundle\EventListener\RefererIdListener;
use Contao\CoreBundle\EventListener\ResponseExceptionListener;
use Contao\CoreBundle\EventListener\StoreRefererListener;
use Contao\CoreBundle\EventListener\ToggleViewListener;
use Contao\CoreBundle\EventListener\UserSessionListener as EventUserSessionListener;
use Contao\CoreBundle\FragmentRegistry\ContentElement\DefaultContentElementRenderer;
use Contao\CoreBundle\FragmentRegistry\ContentElement\DelegatingContentElementRenderer;
use Contao\CoreBundle\FragmentRegistry\FragmentRegistry;
use Contao\CoreBundle\FragmentRegistry\FrontendModule\DefaultFrontendModuleRenderer;
use Contao\CoreBundle\FragmentRegistry\FrontendModule\DelegatingFrontendModuleRenderer;
use Contao\CoreBundle\FragmentRegistry\PageType\DefaultPageTypeRenderer;
use Contao\CoreBundle\FragmentRegistry\PageType\DelegatingPageTypeRenderer;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\Picker\ArticlePickerProvider;
use Contao\CoreBundle\Picker\FilePickerProvider;
use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\PickerBuilder;
use Contao\CoreBundle\Referer\TokenGenerator;
use Contao\CoreBundle\Routing\FrontendLoader;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Security\ContaoAuthenticator;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\Translator;
use Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Doctrine\Common\Cache\FilesystemCache;
use Imagine\Gd\Imagine;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

class ContaoCoreExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getRootDir(),
                'kernel.root_dir' => $this->getRootDir().'/app',
                'kernel.default_locale' => 'en',
            ])
        );

        $params = [
            'contao' => [
                'encryption_key' => 'foobar',
                'localconfig' => ['foo' => 'bar'],
            ],
        ];

        $extension = new ContaoCoreExtension();
        $extension->load($params, $this->container);
    }

    public function testCanBeInstantiated(): void
    {
        $extension = new ContaoCoreExtension();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\ContaoCoreExtension', $extension);
    }

    public function testReturnsTheCorrectAlias(): void
    {
        $extension = new ContaoCoreExtension();

        $this->assertSame('contao', $extension->getAlias());
    }

    /**
     * @param string $key
     * @param string $class
     *
     * @dataProvider getCommandTestData
     */
    public function testRegistersTheCommands(string $key, string $class): void
    {
        $this->assertTrue($this->container->has($key));

        $definition = $this->container->getDefinition($key);

        $this->assertSame($class, $definition->getClass());
        $this->assertTrue($definition->isAutoconfigured());

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setFramework', $methodCalls[0][0]);
    }

    /**
     * @return array
     */
    public function getCommandTestData(): array
    {
        return [
            ['contao.command.automator', AutomatorCommand::class],
            ['contao.command.filesync', FilesyncCommand::class],
            ['contao.command.install', InstallCommand::class],
            ['contao.command.symlinks', SymlinksCommand::class],
            ['contao.command.user_password_command', UserPasswordCommand::class],
            ['contao.command.version', VersionCommand::class],
        ];
    }

    public function testRegistersTheAddToSearchIndexListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.add_to_search_index'));

        $definition = $this->container->getDefinition('contao.listener.add_to_search_index');

        $this->assertSame(AddToSearchIndexListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('%fragment.path%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.terminate', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelTerminate', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheBypassMaintenanceListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.bypass_maintenance'));

        $definition = $this->container->getDefinition('contao.listener.bypass_maintenance');

        $this->assertSame(BypassMaintenanceListener::class, $definition->getClass());
        $this->assertSame('session', (string) $definition->getArgument(0));
        $this->assertSame('%contao.security.disable_ip_check%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(6, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheCommandSchedulerListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.command_scheduler'));

        $definition = $this->container->getDefinition('contao.listener.command_scheduler');

        $this->assertSame(CommandSchedulerListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('database_connection', (string) $definition->getArgument(1));
        $this->assertSame('%fragment.path%', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.terminate', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelTerminate', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheDoctrineSchemaListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.doctrine_schema'));

        $definition = $this->container->getDefinition('contao.listener.doctrine_schema');

        $this->assertSame(DoctrineSchemaListener::class, $definition->getClass());
        $this->assertSame('contao.doctrine.schema_provider', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('doctrine.event_listener', $tags);
        $this->assertSame('onSchemaIndexDefinition', $tags['doctrine.event_listener'][0]['event']);
        $this->assertSame('postGenerateSchema', $tags['doctrine.event_listener'][1]['event']);
    }

    public function testRegistersTheExceptionConverterListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.exception_converter'));

        $definition = $this->container->getDefinition('contao.listener.exception_converter');

        $this->assertSame(ExceptionConverterListener::class, $definition->getClass());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.exception', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelException', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(96, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheHeaderReplayUserSessionListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.header_replay.user_session'));

        $definition = $this->container->getDefinition('contao.listener.header_replay.user_session');

        $this->assertSame(HeaderReplayUserSessionListener::class, $definition->getClass());
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(0));
        $this->assertSame('%contao.security.disable_ip_check%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('terminal42.header_replay', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onReplay', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheHeaderReplayPageLayoutListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.header_replay.page_layout'));

        $definition = $this->container->getDefinition('contao.listener.header_replay.page_layout');

        $this->assertSame(PageLayoutListener::class, $definition->getClass());
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('terminal42.header_replay', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onReplay', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheInsecureInstallationListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.insecure_installation'));

        $definition = $this->container->getDefinition('contao.listener.insecure_installation');

        $this->assertSame(InsecureInstallationListener::class, $definition->getClass());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheLocaleListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.locale'));

        $definition = $this->container->getDefinition('contao.listener.locale');

        $this->assertSame(LocaleListener::class, $definition->getClass());
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(0));
        $this->assertSame('%contao.locales%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(20, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheMapFragmentsToGlobalsListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.map_fragments_to_globals'));

        $definition = $this->container->getDefinition('contao.listener.map_fragments_to_globals');

        $this->assertSame(MapFragmentsToGlobalsListener::class, $definition->getClass());
        $this->assertSame('contao.fragment.registry', (string) $definition->getArgument(0));
    }

    public function testRegistersTheMergeHttpHeadersListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.merge_http_headers'));

        $definition = $this->container->getDefinition('contao.listener.merge_http_headers');

        $this->assertSame(MergeHttpHeadersListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.response', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelResponse', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(256, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersThePrettyErrorScreensListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.pretty_error_screens'));

        $definition = $this->container->getDefinition('contao.listener.pretty_error_screens');

        $this->assertSame(PrettyErrorScreenListener::class, $definition->getClass());
        $this->assertSame('%contao.pretty_error_screens%', (string) $definition->getArgument(0));
        $this->assertSame('twig', (string) $definition->getArgument(1));
        $this->assertSame('contao.framework', (string) $definition->getArgument(2));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(3));
        $this->assertSame('logger', (string) $definition->getArgument(4));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.exception', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelException', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(-96, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheRefererIdListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.referer_id'));

        $definition = $this->container->getDefinition('contao.listener.referer_id');

        $this->assertSame(RefererIdListener::class, $definition->getClass());
        $this->assertSame('contao.referer_id.manager', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(20, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheResponseExceptionListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.response_exception'));

        $definition = $this->container->getDefinition('contao.listener.response_exception');

        $this->assertSame(ResponseExceptionListener::class, $definition->getClass());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.exception', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelException', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(64, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheStoreRefererListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.store_referer'));

        $definition = $this->container->getDefinition('contao.listener.store_referer');

        $this->assertSame(StoreRefererListener::class, $definition->getClass());
        $this->assertSame('session', (string) $definition->getArgument(0));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(1));
        $this->assertSame('security.authentication.trust_resolver', (string) $definition->getArgument(2));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(3));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.response', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelResponse', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheToggleViewListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.toggle_view'));

        $definition = $this->container->getDefinition('contao.listener.toggle_view');

        $this->assertSame(ToggleViewListener::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheCsrfTokenCookieListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.csrf_token_cookie'));

        $definition = $this->container->getDefinition('contao.listener.csrf_token_cookie');

        $this->assertSame(CsrfTokenCookieListener::class, $definition->getClass());
        $this->assertSame('contao.csrf.token_storage', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(20, $tags['kernel.event_listener'][0]['priority']);
        $this->assertSame('kernel.response', $tags['kernel.event_listener'][1]['event']);
        $this->assertSame('onKernelResponse', $tags['kernel.event_listener'][1]['method']);
    }

    public function testRegistersTheUserSessionListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.user_session'));

        $definition = $this->container->getDefinition('contao.listener.user_session');

        $this->assertSame(EventUserSessionListener::class, $definition->getClass());
        $this->assertSame('session', (string) $definition->getArgument(0));
        $this->assertSame('database_connection', (string) $definition->getArgument(1));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(2));
        $this->assertSame('security.authentication.trust_resolver', (string) $definition->getArgument(3));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(4));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame('kernel.response', $tags['kernel.event_listener'][1]['event']);
        $this->assertSame('onKernelResponse', $tags['kernel.event_listener'][1]['method']);
    }

    public function testRegistersTheArgumentResolverModel(): void
    {
        $this->assertTrue($this->container->has('contao.argument_resolver.model'));

        $definition = $this->container->getDefinition('contao.argument_resolver.model');

        $this->assertSame(ModelResolver::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('controller.argument_value_resolver', $tags);
        $this->assertSame(101, $tags['controller.argument_value_resolver'][0]['priority']);
    }

    public function testRegistersTheContaoCache(): void
    {
        $this->assertTrue($this->container->has('contao.cache'));

        $definition = $this->container->getDefinition('contao.cache');

        $this->assertSame(FilesystemCache::class, $definition->getClass());
        $this->assertSame('%kernel.cache_dir%/contao/cache', (string) $definition->getArgument(0));
        $this->assertSame('', (string) $definition->getArgument(1));
        $this->assertSame('18', (string) $definition->getArgument(2));
    }

    public function testRegistersTheContaoCacheClearer(): void
    {
        $this->assertTrue($this->container->has('contao.cache.clear_internal'));

        $definition = $this->container->getDefinition('contao.cache.clear_internal');

        $this->assertSame(ContaoCacheClearer::class, $definition->getClass());
        $this->assertSame('filesystem', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.cache_clearer', $tags);
    }

    public function testRegistersTheContaoCacheWarmer(): void
    {
        $this->assertTrue($this->container->has('contao.cache.warm_internal'));

        $definition = $this->container->getDefinition('contao.cache.warm_internal');

        $this->assertSame(ContaoCacheWarmer::class, $definition->getClass());
        $this->assertSame('filesystem', (string) $definition->getArgument(0));
        $this->assertSame('contao.resource_finder', (string) $definition->getArgument(1));
        $this->assertSame('contao.resource_locator', (string) $definition->getArgument(2));
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(3));
        $this->assertSame('database_connection', (string) $definition->getArgument(4));
        $this->assertSame('contao.framework', (string) $definition->getArgument(5));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.cache_warmer', $tags);
    }

    public function testRegistersTheBackendCsvImportController(): void
    {
        $this->assertTrue($this->container->has('contao.controller.backend_csv_import'));

        $definition = $this->container->getDefinition('contao.controller.backend_csv_import');

        $this->assertSame(BackendCsvImportController::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('database_connection', (string) $definition->getArgument(1));
        $this->assertSame('request_stack', (string) $definition->getArgument(2));
        $this->assertSame('contao.translation.translator', (string) $definition->getArgument(3));
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(4));
    }

    public function tesRegistersThetInsertTagsController(): void
    {
        $this->assertTrue($this->container->has('contao.controller.insert_tags'));

        $definition = $this->container->getDefinition('contao.controller.insert_tags');

        $this->assertSame(InsertTagsController::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    public function testRegistersTheCorsWebsiteRootsConfigProvider(): void
    {
        $this->assertTrue($this->container->has('contao.cors_website_roots_config_provider'));

        $definition = $this->container->getDefinition('contao.cors_website_roots_config_provider');

        $this->assertSame(WebsiteRootsConfigProvider::class, $definition->getClass());
        $this->assertSame('database_connection', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('nelmio_cors.options_provider', $tags);
    }

    public function testRegistersTheDataCollector(): void
    {
        $this->assertTrue($this->container->has('contao.data_collector'));

        $definition = $this->container->getDefinition('contao.data_collector');

        $this->assertSame(ContaoDataCollector::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('%kernel.packages%', (string) $definition->getArgument(0));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setFramework', $methodCalls[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('data_collector', $tags);
        $this->assertSame('ContaoCoreBundle:Collector:contao', $tags['data_collector'][0]['template']);
        $this->assertSame('contao', $tags['data_collector'][0]['id']);
    }

    public function testRegistersTheDoctrineSchemaProvider(): void
    {
        $this->assertTrue($this->container->has('contao.doctrine.schema_provider'));

        $definition = $this->container->getDefinition('contao.doctrine.schema_provider');

        $this->assertSame(DcaSchemaProvider::class, $definition->getClass());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('doctrine', (string) $definition->getArgument(1));
    }

    public function testRegistersTheFragmentRegistry(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.registry'));

        $definition = $this->container->getDefinition('contao.fragment.registry');

        $this->assertSame(FragmentRegistry::class, $definition->getClass());
    }

    public function testRegistersTheFragmentRendererContentElement(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.content_element'));
    }

    public function testRegistersTheFragmentRendererContentElementDefault(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.content_element.default'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.content_element.default');

        $this->assertSame(DefaultContentElementRenderer::class, $definition->getClass());
        $this->assertSame('contao.fragment.registry', (string) $definition->getArgument(0));
        $this->assertSame('fragment.handler', (string) $definition->getArgument(1));
        $this->assertSame('request_stack', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.fragment.renderer.content_element', $tags);
    }

    public function testRegistersTheFragmentRendererContentElementDelegating(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.content_element.delegating'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.content_element.delegating');

        $this->assertSame(DelegatingContentElementRenderer::class, $definition->getClass());
    }

    public function testRegistersTheFragmentRendererFrontendModule(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.frontend_module'));
    }

    public function testRegistersTheFragmentRendererFrontendModuleDefault(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.frontend_module.default'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.frontend_module.default');

        $this->assertSame(DefaultFrontendModuleRenderer::class, $definition->getClass());
        $this->assertSame('contao.fragment.registry', (string) $definition->getArgument(0));
        $this->assertSame('fragment.handler', (string) $definition->getArgument(1));
        $this->assertSame('request_stack', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.fragment.renderer.frontend_module', $tags);
    }

    public function testRegistersTheFragmentRendererFrontendModuleDelegating(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.frontend_module.delegating'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.frontend_module.delegating');

        $this->assertSame(DelegatingFrontendModuleRenderer::class, $definition->getClass());
    }

    public function testRegistersTheFragmentRendererPageType(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.page_type'));
    }

    public function testRegistersTheFragmentRendererPageTypeDefault(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.page_type.default'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.page_type.default');

        $this->assertSame(DefaultPageTypeRenderer::class, $definition->getClass());
        $this->assertSame('contao.fragment.registry', (string) $definition->getArgument(0));
        $this->assertSame('fragment.handler', (string) $definition->getArgument(1));
        $this->assertSame('request_stack', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.fragment.renderer.page_type', $tags);
    }

    public function testRegistersTheFragmentRendererPageTypeDelegating(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.page_type.delegating'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.page_type.delegating');

        $this->assertSame(DelegatingPageTypeRenderer::class, $definition->getClass());
    }

    public function testRegistersTheImageImagineService(): void
    {
        $this->assertTrue($this->container->has('contao.image.imagine'));

        $definition = $this->container->getDefinition('contao.image.imagine');

        $this->assertSame(Imagine::class, $definition->getClass());
    }

    public function testRegistersTheImageImagineSvgService(): void
    {
        $this->assertTrue($this->container->has('contao.image.imagine_svg'));

        $definition = $this->container->getDefinition('contao.image.imagine_svg');

        $this->assertSame(ImagineSvg::class, $definition->getClass());
    }

    public function testRegistersTheImageResizeCalculator(): void
    {
        $this->assertTrue($this->container->has('contao.image.resize_calculator'));

        $definition = $this->container->getDefinition('contao.image.resize_calculator');

        $this->assertSame(ResizeCalculator::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    public function testRegistersTheImageResizer(): void
    {
        $this->assertTrue($this->container->has('contao.image.resizer'));

        $definition = $this->container->getDefinition('contao.image.resizer');

        $this->assertSame(LegacyResizer::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('%contao.image.target_dir%', (string) $definition->getArgument(0));
        $this->assertSame('contao.image.resize_calculator', (string) $definition->getArgument(1));
        $this->assertSame('filesystem', (string) $definition->getArgument(2));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setFramework', $methodCalls[0][0]);
    }

    public function testRegistersTheImageFactory(): void
    {
        $this->assertTrue($this->container->has('contao.image.image_factory'));

        $definition = $this->container->getDefinition('contao.image.image_factory');

        $this->assertSame(ImageFactory::class, $definition->getClass());
        $this->assertSame('contao.image.resizer', (string) $definition->getArgument(0));
        $this->assertSame('contao.image.imagine', (string) $definition->getArgument(1));
        $this->assertSame('contao.image.imagine_svg', (string) $definition->getArgument(2));
        $this->assertSame('filesystem', (string) $definition->getArgument(3));
        $this->assertSame('contao.framework', (string) $definition->getArgument(4));
        $this->assertSame('%contao.image.bypass_cache%', (string) $definition->getArgument(5));
        $this->assertSame('%contao.image.imagine_options%', (string) $definition->getArgument(6));
        $this->assertSame('%contao.image.valid_extensions%', (string) $definition->getArgument(7));
    }

    public function testRegistersTheImageSizesService(): void
    {
        $this->assertTrue($this->container->has('contao.image.image_sizes'));

        $definition = $this->container->getDefinition('contao.image.image_sizes');

        $this->assertSame(ImageSizes::class, $definition->getClass());
        $this->assertSame('database_connection', (string) $definition->getArgument(0));
        $this->assertSame('event_dispatcher', (string) $definition->getArgument(1));
        $this->assertSame('contao.framework', (string) $definition->getArgument(2));
    }

    public function testRegistersTheImagePictureGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.image.picture_generator'));

        $definition = $this->container->getDefinition('contao.image.picture_generator');

        $this->assertSame(PictureGenerator::class, $definition->getClass());
        $this->assertSame('contao.image.resizer', (string) $definition->getArgument(0));
    }

    public function testRegistersTheImagePictureFactory(): void
    {
        $this->assertTrue($this->container->has('contao.image.picture_factory'));

        $definition = $this->container->getDefinition('contao.image.picture_factory');

        $this->assertSame(PictureFactory::class, $definition->getClass());
        $this->assertSame('contao.image.picture_generator', (string) $definition->getArgument(0));
        $this->assertSame('contao.image.image_factory', (string) $definition->getArgument(1));
        $this->assertSame('contao.framework', (string) $definition->getArgument(2));
        $this->assertSame('%contao.image.bypass_cache%', (string) $definition->getArgument(3));
        $this->assertSame('%contao.image.imagine_options%', (string) $definition->getArgument(4));
    }

    public function testRegistersTheContaoFramework(): void
    {
        $this->assertTrue($this->container->has('contao.framework'));

        $definition = $this->container->getDefinition('contao.framework');

        $this->assertSame(ContaoFramework::class, $definition->getClass());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('session', (string) $definition->getArgument(2));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(3));
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(4));
        $this->assertSame('%contao.error_level%', (string) $definition->getArgument(5));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[ContainerAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setContainer', $methodCalls[0][0]);
    }

    public function testRegistersTheMenuMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.menu.matcher'));

        $definition = $this->container->getDefinition('contao.menu.matcher');

        $this->assertSame(Matcher::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    public function testRegistersTheMenuRenderer(): void
    {
        $this->assertTrue($this->container->has('contao.menu.renderer'));

        $definition = $this->container->getDefinition('contao.menu.renderer');

        $this->assertSame(ListRenderer::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.menu.matcher', (string) $definition->getArgument(0));
    }

    public function testRegistersTheMonologHandler(): void
    {
        $this->assertTrue($this->container->has('contao.monolog.handler'));

        $definition = $this->container->getDefinition('contao.monolog.handler');

        $this->assertSame(ContaoTableHandler::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('debug', (string) $definition->getArgument(0));
        $this->assertSame('', (string) $definition->getArgument(1));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[ContainerAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setContainer', $methodCalls[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('monolog.logger', $tags);
        $this->assertSame('contao', $tags['monolog.logger'][0]['channel']);
    }

    public function testRegistersTheMonologProcessor(): void
    {
        $this->assertTrue($this->container->has('contao.monolog.processor'));

        $definition = $this->container->getDefinition('contao.monolog.processor');

        $this->assertSame(ContaoTableProcessor::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(1));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('monolog.processor', $tags);
    }

    public function testRegistersThePickerBuilder(): void
    {
        $this->assertTrue($this->container->has('contao.picker.builder'));

        $definition = $this->container->getDefinition('contao.picker.builder');

        $this->assertSame(PickerBuilder::class, $definition->getClass());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('request_stack', (string) $definition->getArgument(2));
    }

    public function testRegistersThePagePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.page_provider'));

        $definition = $this->container->getDefinition('contao.picker.page_provider');

        $this->assertSame(PagePickerProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('contao.translation.translator', (string) $definition->getArgument(2));

        $calls = $definition->getMethodCalls();

        $this->assertSame('setTokenStorage', $calls[0][0]);
        $this->assertSame('security.token_storage', (string) $calls[0][1][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_provider', $tags);
        $this->assertSame(192, $tags['contao.picker_provider'][0]['priority']);
    }

    public function testRegistersTheFilePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.file_provider'));

        $definition = $this->container->getDefinition('contao.picker.file_provider');

        $this->assertSame(FilePickerProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('contao.translation.translator', (string) $definition->getArgument(2));
        $this->assertSame('%contao.upload_path%', (string) $definition->getArgument(3));

        $calls = $definition->getMethodCalls();

        $this->assertSame('setTokenStorage', $calls[0][0]);
        $this->assertSame('security.token_storage', (string) $calls[0][1][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_provider', $tags);
        $this->assertSame(160, $tags['contao.picker_provider'][0]['priority']);
    }

    public function testRegistersTheArticlePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.article_provider'));

        $definition = $this->container->getDefinition('contao.picker.article_provider');

        $this->assertSame(ArticlePickerProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('contao.translation.translator', (string) $definition->getArgument(2));

        $calls = $definition->getMethodCalls();

        $this->assertSame('setTokenStorage', $calls[0][0]);
        $this->assertSame('security.token_storage', (string) $calls[0][1][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_provider', $tags);
    }

    public function testRegistersTheRefererIdManager(): void
    {
        $this->assertTrue($this->container->has('contao.referer_id.manager'));

        $definition = $this->container->getDefinition('contao.referer_id.manager');

        $this->assertSame(CsrfTokenManager::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('contao.referer_id.token_generator', (string) $definition->getArgument(0));
        $this->assertSame('security.csrf.token_storage', (string) $definition->getArgument(1));
    }

    public function testRegistersTheRefererIdTokenGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.referer_id.token_generator'));

        $definition = $this->container->getDefinition('contao.referer_id.token_generator');

        $this->assertSame(TokenGenerator::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    public function testRegistersTheResourceFinder(): void
    {
        $this->assertTrue($this->container->has('contao.resource_finder'));

        $definition = $this->container->getDefinition('contao.resource_finder');

        $this->assertSame(ResourceFinder::class, $definition->getClass());
        $this->assertSame('%contao.resources_paths%', $definition->getArgument(0));
    }

    public function testRegistersTheResourceLocator(): void
    {
        $this->assertTrue($this->container->has('contao.resource_locator'));

        $definition = $this->container->getDefinition('contao.resource_locator');

        $this->assertSame(FileLocator::class, $definition->getClass());
        $this->assertSame('%contao.resources_paths%', $definition->getArgument(0));
    }

    public function testRegistersTheRoutingFrontendLoader(): void
    {
        $this->assertTrue($this->container->has('contao.routing.frontend_loader'));

        $definition = $this->container->getDefinition('contao.routing.frontend_loader');

        $this->assertSame(FrontendLoader::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('%contao.prepend_locale%', $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('routing.loader', $tags);
    }

    public function testRegistersTheRoutingUrlGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.routing.url_generator'));

        $definition = $this->container->getDefinition('contao.routing.url_generator');

        $this->assertSame(UrlGenerator::class, $definition->getClass());
        $this->assertSame('router', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));
        $this->assertSame('%contao.prepend_locale%', (string) $definition->getArgument(2));
    }

    public function testRegistersTheRoutingScopeMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.scope_matcher'));

        $definition = $this->container->getDefinition('contao.routing.scope_matcher');

        $this->assertSame(ScopeMatcher::class, $definition->getClass());
        $this->assertSame('contao.routing.backend_matcher', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.frontend_matcher', (string) $definition->getArgument(1));
    }

    public function testRegistersTheRoutingBackendMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.backend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.backend_matcher');

        $this->assertSame(RequestMatcher::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('matchAttribute', $methodCalls[0][0]);
        $this->assertSame(['_scope', 'backend'], $methodCalls[0][1]);
    }

    public function testRegistersTheRoutingFrontendMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.frontend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.frontend_matcher');

        $this->assertSame(RequestMatcher::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('matchAttribute', $methodCalls[0][0]);
        $this->assertSame(['_scope', 'frontend'], $methodCalls[0][1]);
    }

    public function testRegistersTheSecurityAuthenticator(): void
    {
        $this->assertTrue($this->container->has('contao.security.authenticator'));

        $definition = $this->container->getDefinition('contao.security.authenticator');

        $this->assertSame(ContaoAuthenticator::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(0));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[ContainerAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setContainer', $methodCalls[0][0]);
    }

    public function testRegistersTheSecurityUserProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.user_provider'));

        $definition = $this->container->getDefinition('contao.security.user_provider');

        $this->assertSame(ContaoUserProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(1));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[ContainerAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setContainer', $methodCalls[0][0]);
    }

    public function testRegistersTheContaoBackendSession(): void
    {
        $this->assertTrue($this->container->has('contao.session.contao_backend'));

        $definition = $this->container->getDefinition('contao.session.contao_backend');

        $this->assertSame(ArrayAttributeBag::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('_contao_be_attributes', (string) $definition->getArgument(0));

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('setName', $methodCalls[0][0]);
        $this->assertSame(['contao_backend'], $methodCalls[0][1]);
    }

    public function testRegistersTheContaoFrontendSession(): void
    {
        $this->assertTrue($this->container->has('contao.session.contao_frontend'));

        $definition = $this->container->getDefinition('contao.session.contao_frontend');

        $this->assertSame(ArrayAttributeBag::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('_contao_fe_attributes', (string) $definition->getArgument(0));

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('setName', $methodCalls[0][0]);
        $this->assertSame(['contao_frontend'], $methodCalls[0][1]);
    }

    public function testRegistersTheCsrfTokenManager(): void
    {
        $this->assertTrue($this->container->has('contao.csrf.token_manager'));

        $definition = $this->container->getDefinition('contao.csrf.token_manager');

        $this->assertSame(CsrfTokenManager::class, $definition->getClass());
        $this->assertSame('security.csrf.token_generator', (string) $definition->getArgument(0));
        $this->assertSame('contao.csrf.token_storage', (string) $definition->getArgument(1));
    }

    public function testRegistersTheCsrfTokenStorage(): void
    {
        $this->assertTrue($this->container->has('contao.csrf.token_storage'));

        $definition = $this->container->getDefinition('contao.csrf.token_storage');

        $this->assertSame(MemoryTokenStorage::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    public function testRegistersTheContaoTranslator(): void
    {
        $this->assertTrue($this->container->has('contao.translation.translator'));

        $definition = $this->container->getDefinition('contao.translation.translator');

        $this->assertSame(Translator::class, $definition->getClass());
        $this->assertSame('translator', $definition->getDecoratedService()[0]);
        $this->assertSame('contao.translation.translator.inner', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));
    }

    public function testRegistersTheTwigTemplateExtension(): void
    {
        $this->assertTrue($this->container->has('contao.twig.template_extension'));

        $definition = $this->container->getDefinition('contao.twig.template_extension');

        $this->assertSame(ContaoTemplateExtension::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('twig.extension', $tags);
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the contao.image.target_path parameter has been deprecated %s.
     */
    public function testRegistersTheImageTargetPath(): void
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getRootDir(),
                'kernel.root_dir' => $this->getRootDir().'/app',
                'kernel.default_locale' => 'en',
            ])
        );

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $this->assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->getRootDir().'/assets/images'),
            $container->getParameter('contao.image.target_dir')
        );

        $params = [
            'contao' => [
                'image' => ['target_path' => 'my/custom/dir'],
            ],
        ];

        $extension = new ContaoCoreExtension();
        $extension->load($params, $container);

        $this->assertSame(
            str_replace('/', DIRECTORY_SEPARATOR, $this->getRootDir()).'/my/custom/dir',
            $container->getParameter('contao.image.target_dir')
        );
    }
}
