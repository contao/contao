<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\DependencyInjection;

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
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Contao\CoreBundle\EventListener\BypassMaintenanceListener;
use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\EventListener\ExceptionConverterListener;
use Contao\CoreBundle\EventListener\InsecureInstallationListener;
use Contao\CoreBundle\EventListener\LocaleListener;
use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\EventListener\PrettyErrorScreenListener;
use Contao\CoreBundle\EventListener\RefererIdListener;
use Contao\CoreBundle\EventListener\ResponseExceptionListener;
use Contao\CoreBundle\EventListener\StoreRefererListener;
use Contao\CoreBundle\EventListener\ToggleViewListener;
use Contao\CoreBundle\EventListener\UserSessionListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Menu\ArticlePickerProvider;
use Contao\CoreBundle\Menu\FilePickerProvider;
use Contao\CoreBundle\Menu\PagePickerProvider;
use Contao\CoreBundle\Menu\PickerMenuBuilder;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\Referer\TokenGenerator;
use Contao\CoreBundle\Routing\FrontendLoader;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Security\ContaoAuthenticator;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Doctrine\Common\Cache\FilesystemCache;
use Imagine\Gd\Imagine;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\Security\Csrf\CsrfTokenManager;

/**
 * Tests the ContaoCoreExtension class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContaoCoreExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getRootDir(),
                'kernel.root_dir' => $this->getRootDir().'/app',
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

    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $extension = new ContaoCoreExtension();

        $this->assertInstanceOf('Contao\CoreBundle\DependencyInjection\ContaoCoreExtension', $extension);
    }

    /**
     * Tests the getAlias() method.
     */
    public function testGetAlias()
    {
        $extension = new ContaoCoreExtension();

        $this->assertEquals('contao', $extension->getAlias());
    }

    /**
     * Tests the contao.command.automator command.
     *
     * @param string $key
     * @param string $class
     *
     * @dataProvider getCommandTestData
     */
    public function testCommands($key, $class)
    {
        $this->assertTrue($this->container->has($key));

        $definition = $this->container->getDefinition($key);

        $this->assertEquals($class, $definition->getClass());
        $this->assertTrue($definition->isAutoconfigured());

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertEquals('setFramework', $methodCalls[0][0]);
    }

    /**
     * Tests the contao.listener.add_to_search_index service.
     */
    public function testAddToSearchIndexListener()
    {
        $this->assertTrue($this->container->has('contao.listener.add_to_search_index'));

        $definition = $this->container->getDefinition('contao.listener.add_to_search_index');

        $this->assertEquals(AddToSearchIndexListener::class, $definition->getClass());
        $this->assertEquals('contao.framework', (string) $definition->getArgument(0));
        $this->assertEquals('%fragment.path%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.terminate', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelTerminate', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao.listener.bypass_maintenance service.
     */
    public function testBypassMaintenanceListener()
    {
        $this->assertTrue($this->container->has('contao.listener.bypass_maintenance'));

        $definition = $this->container->getDefinition('contao.listener.bypass_maintenance');

        $this->assertEquals(BypassMaintenanceListener::class, $definition->getClass());
        $this->assertEquals('session', (string) $definition->getArgument(0));
        $this->assertEquals('%contao.security.disable_ip_check%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals(10, $tags['kernel.event_listener'][0]['priority']);
    }

    /**
     * Tests the contao.listener.command_scheduler service.
     */
    public function testCommandSchedulerListener()
    {
        $this->assertTrue($this->container->has('contao.listener.command_scheduler'));

        $definition = $this->container->getDefinition('contao.listener.command_scheduler');

        $this->assertEquals(CommandSchedulerListener::class, $definition->getClass());
        $this->assertEquals('contao.framework', (string) $definition->getArgument(0));
        $this->assertEquals('database_connection', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.terminate', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelTerminate', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao.listener.doctrine_schema service.
     */
    public function testDoctrineSchemaListener()
    {
        $this->assertTrue($this->container->has('contao.listener.doctrine_schema'));

        $definition = $this->container->getDefinition('contao.listener.doctrine_schema');

        $this->assertEquals(DoctrineSchemaListener::class, $definition->getClass());
        $this->assertEquals('contao.doctrine.schema_provider', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('doctrine.event_listener', $tags);
        $this->assertEquals('onSchemaIndexDefinition', $tags['doctrine.event_listener'][0]['event']);
        $this->assertEquals('postGenerateSchema', $tags['doctrine.event_listener'][1]['event']);
    }

    /**
     * Tests the contao.listener.exception_converter service.
     */
    public function testExceptionConverterListener()
    {
        $this->assertTrue($this->container->has('contao.listener.exception_converter'));

        $definition = $this->container->getDefinition('contao.listener.exception_converter');

        $this->assertEquals(ExceptionConverterListener::class, $definition->getClass());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.exception', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelException', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals(96, $tags['kernel.event_listener'][0]['priority']);
    }

    /**
     * Tests the contao.listener.insecure_installation service.
     */
    public function testInsecureInstallationListener()
    {
        $this->assertTrue($this->container->has('contao.listener.insecure_installation'));

        $definition = $this->container->getDefinition('contao.listener.insecure_installation');

        $this->assertEquals(InsecureInstallationListener::class, $definition->getClass());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao.listener.locale service.
     */
    public function testLocaleListener()
    {
        $this->assertTrue($this->container->has('contao.listener.locale'));

        $definition = $this->container->getDefinition('contao.listener.locale');

        $this->assertEquals(LocaleListener::class, $definition->getClass());
        $this->assertEquals('contao.routing.scope_matcher', (string) $definition->getArgument(0));
        $this->assertEquals('%kernel.default_locale%', (string) $definition->getArgument(1));
        $this->assertEquals('%kernel.root_dir%', (string) $definition->getArgument(2));
        $this->assertEquals([LocaleListener::class, 'createWithLocales'], $definition->getFactory());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals(20, $tags['kernel.event_listener'][0]['priority']);
    }

    /**
     * Tests the contao.listener.merge_http_headers service.
     */
    public function testMergeHttpHeadersListener()
    {
        $this->assertTrue($this->container->has('contao.listener.merge_http_headers'));

        $definition = $this->container->getDefinition('contao.listener.merge_http_headers');

        $this->assertEquals(MergeHttpHeadersListener::class, $definition->getClass());
        $this->assertEquals('contao.framework', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.response', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelResponse', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals(256, $tags['kernel.event_listener'][0]['priority']);
    }

    /**
     * Tests the contao.listener.pretty_error_screens service.
     */
    public function testPrettyErrorScreensListener()
    {
        $this->assertTrue($this->container->has('contao.listener.pretty_error_screens'));

        $definition = $this->container->getDefinition('contao.listener.pretty_error_screens');

        $this->assertEquals(PrettyErrorScreenListener::class, $definition->getClass());
        $this->assertEquals('%contao.pretty_error_screens%', (string) $definition->getArgument(0));
        $this->assertEquals('twig', (string) $definition->getArgument(1));
        $this->assertEquals('contao.framework', (string) $definition->getArgument(2));
        $this->assertEquals('security.token_storage', (string) $definition->getArgument(3));
        $this->assertEquals('logger', (string) $definition->getArgument(4));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.exception', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelException', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals(-96, $tags['kernel.event_listener'][0]['priority']);
    }

    /**
     * Tests the contao.listener.referer_id service.
     */
    public function testRefererIdListener()
    {
        $this->assertTrue($this->container->has('contao.listener.referer_id'));

        $definition = $this->container->getDefinition('contao.listener.referer_id');

        $this->assertEquals(RefererIdListener::class, $definition->getClass());
        $this->assertEquals('contao.referer_id.manager', (string) $definition->getArgument(0));
        $this->assertEquals('contao.routing.scope_matcher', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals(20, $tags['kernel.event_listener'][0]['priority']);
    }

    /**
     * Tests the contao.listener.response_exception service.
     */
    public function testResponseExceptionListener()
    {
        $this->assertTrue($this->container->has('contao.listener.response_exception'));

        $definition = $this->container->getDefinition('contao.listener.response_exception');

        $this->assertEquals(ResponseExceptionListener::class, $definition->getClass());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.exception', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelException', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals(64, $tags['kernel.event_listener'][0]['priority']);
    }

    /**
     * Tests the contao.listener.store_referer service.
     */
    public function testStoreRefererListener()
    {
        $this->assertTrue($this->container->has('contao.listener.store_referer'));

        $definition = $this->container->getDefinition('contao.listener.store_referer');

        $this->assertEquals(StoreRefererListener::class, $definition->getClass());
        $this->assertEquals('session', (string) $definition->getArgument(0));
        $this->assertEquals('security.token_storage', (string) $definition->getArgument(1));
        $this->assertEquals('security.authentication.trust_resolver', (string) $definition->getArgument(2));
        $this->assertEquals('contao.routing.scope_matcher', (string) $definition->getArgument(3));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.response', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelResponse', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao.listener.toggle_view service.
     */
    public function testToggleViewListener()
    {
        $this->assertTrue($this->container->has('contao.listener.toggle_view'));

        $definition = $this->container->getDefinition('contao.listener.toggle_view');

        $this->assertEquals(ToggleViewListener::class, $definition->getClass());
        $this->assertEquals('contao.framework', (string) $definition->getArgument(0));
        $this->assertEquals('contao.routing.scope_matcher', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
    }

    /**
     * Tests the contao.listener.user_session service.
     */
    public function testUserSessionListener()
    {
        $this->assertTrue($this->container->has('contao.listener.user_session'));

        $definition = $this->container->getDefinition('contao.listener.user_session');

        $this->assertEquals(UserSessionListener::class, $definition->getClass());
        $this->assertEquals('session', (string) $definition->getArgument(0));
        $this->assertEquals('database_connection', (string) $definition->getArgument(1));
        $this->assertEquals('security.token_storage', (string) $definition->getArgument(2));
        $this->assertEquals('security.authentication.trust_resolver', (string) $definition->getArgument(3));
        $this->assertEquals('contao.routing.scope_matcher', (string) $definition->getArgument(4));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertEquals('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertEquals('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertEquals('kernel.response', $tags['kernel.event_listener'][1]['event']);
        $this->assertEquals('onKernelResponse', $tags['kernel.event_listener'][1]['method']);
    }

    /**
     * Tests the contao.cache service.
     */
    public function testCache()
    {
        $this->assertTrue($this->container->has('contao.cache'));

        $definition = $this->container->getDefinition('contao.cache');

        $this->assertEquals(FilesystemCache::class, $definition->getClass());
        $this->assertEquals('%kernel.cache_dir%/contao/cache', (string) $definition->getArgument(0));
        $this->assertEquals('', (string) $definition->getArgument(1));
        $this->assertEquals(0022, (string) $definition->getArgument(2));
    }

    /**
     * Tests the contao.cache.clear_internal service.
     */
    public function testClearInternalCache()
    {
        $this->assertTrue($this->container->has('contao.cache.clear_internal'));

        $definition = $this->container->getDefinition('contao.cache.clear_internal');

        $this->assertEquals(ContaoCacheClearer::class, $definition->getClass());
        $this->assertEquals('filesystem', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.cache_clearer', $tags);
    }

    /**
     * Tests the contao.cache.warm_internal service.
     */
    public function testWarmInternalCache()
    {
        $this->assertTrue($this->container->has('contao.cache.warm_internal'));

        $definition = $this->container->getDefinition('contao.cache.warm_internal');

        $this->assertEquals(ContaoCacheWarmer::class, $definition->getClass());
        $this->assertEquals('filesystem', (string) $definition->getArgument(0));
        $this->assertEquals('contao.resource_finder', (string) $definition->getArgument(1));
        $this->assertEquals('contao.resource_locator', (string) $definition->getArgument(2));
        $this->assertEquals('%kernel.project_dir%', (string) $definition->getArgument(3));
        $this->assertEquals('database_connection', (string) $definition->getArgument(4));
        $this->assertEquals('contao.framework', (string) $definition->getArgument(5));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.cache_warmer', $tags);
    }

    /**
     * Tests the contao.controller.backend_csv_import service.
     */
    public function testBackendCsvImportController()
    {
        $this->assertTrue($this->container->has('contao.controller.backend_csv_import'));

        $definition = $this->container->getDefinition('contao.controller.backend_csv_import');

        $this->assertEquals(BackendCsvImportController::class, $definition->getClass());
        $this->assertEquals('contao.framework', (string) $definition->getArgument(0));
        $this->assertEquals('database_connection', (string) $definition->getArgument(1));
        $this->assertEquals('request_stack', (string) $definition->getArgument(2));
        $this->assertEquals('%kernel.project_dir%', (string) $definition->getArgument(3));
    }

    /**
     * Tests the contao.controller.insert_tags service.
     */
    public function testInsertTagsController()
    {
        $this->assertTrue($this->container->has('contao.controller.insert_tags'));

        $definition = $this->container->getDefinition('contao.controller.insert_tags');

        $this->assertEquals(InsertTagsController::class, $definition->getClass());
        $this->assertEquals('contao.framework', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao.cors_website_roots_config_provider service.
     */
    public function testCorsWebsiteRootsConfigProvider()
    {
        $this->assertTrue($this->container->has('contao.cors_website_roots_config_provider'));

        $definition = $this->container->getDefinition('contao.cors_website_roots_config_provider');

        $this->assertEquals(WebsiteRootsConfigProvider::class, $definition->getClass());
        $this->assertEquals('database_connection', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('nelmio_cors.options_provider', $tags);
    }

    /**
     * Tests the contao.data_collector service.
     */
    public function testDataCollector()
    {
        $this->assertTrue($this->container->has('contao.data_collector'));

        $definition = $this->container->getDefinition('contao.data_collector');

        $this->assertEquals(ContaoDataCollector::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('%kernel.packages%', (string) $definition->getArgument(0));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertEquals('setFramework', $methodCalls[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('data_collector', $tags);
        $this->assertEquals('ContaoCoreBundle:Collector:contao', $tags['data_collector'][0]['template']);
        $this->assertEquals('contao', $tags['data_collector'][0]['id']);
    }

    /**
     * Tests the contao.doctrine.schema_provider service.
     */
    public function testDoctrineSchemaProvider()
    {
        $this->assertTrue($this->container->has('contao.doctrine.schema_provider'));

        $definition = $this->container->getDefinition('contao.doctrine.schema_provider');

        $this->assertEquals(DcaSchemaProvider::class, $definition->getClass());
        $this->assertEquals('contao.framework', (string) $definition->getArgument(0));
        $this->assertEquals('doctrine', (string) $definition->getArgument(1));
    }

    /**
     * Tests the contao.image.imagine service.
     */
    public function testImageImagine()
    {
        $this->assertTrue($this->container->has('contao.image.imagine'));

        $definition = $this->container->getDefinition('contao.image.imagine');

        $this->assertEquals(Imagine::class, $definition->getClass());
    }

    /**
     * Tests the contao.image.imagine_svg service.
     */
    public function testImageImagineSvg()
    {
        $this->assertTrue($this->container->has('contao.image.imagine_svg'));

        $definition = $this->container->getDefinition('contao.image.imagine_svg');

        $this->assertEquals(ImagineSvg::class, $definition->getClass());
    }

    /**
     * Tests the contao.image.resize_calculator service.
     */
    public function testImageResizeCalculator()
    {
        $this->assertTrue($this->container->has('contao.image.resize_calculator'));

        $definition = $this->container->getDefinition('contao.image.resize_calculator');

        $this->assertEquals(ResizeCalculator::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    /**
     * Tests the contao.image.resizer service.
     */
    public function testImageResizer()
    {
        $this->assertTrue($this->container->has('contao.image.resizer'));

        $definition = $this->container->getDefinition('contao.image.resizer');

        $this->assertEquals(LegacyResizer::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('%contao.image.target_dir%', $definition->getArgument(0));
        $this->assertEquals('contao.image.resize_calculator', $definition->getArgument(1));
        $this->assertEquals('filesystem', $definition->getArgument(2));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[FrameworkAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertEquals('setFramework', $methodCalls[0][0]);
    }

    /**
     * Tests the contao.image.image_factory service.
     */
    public function testImageFactory()
    {
        $this->assertTrue($this->container->has('contao.image.image_factory'));

        $definition = $this->container->getDefinition('contao.image.image_factory');

        $this->assertEquals(ImageFactory::class, $definition->getClass());
        $this->assertEquals('contao.image.resizer', $definition->getArgument(0));
        $this->assertEquals('contao.image.imagine', $definition->getArgument(1));
        $this->assertEquals('contao.image.imagine_svg', $definition->getArgument(2));
        $this->assertEquals('filesystem', $definition->getArgument(3));
        $this->assertEquals('contao.framework', $definition->getArgument(4));
        $this->assertEquals('%contao.image.bypass_cache%', $definition->getArgument(5));
        $this->assertEquals('%contao.image.imagine_options%', $definition->getArgument(6));
        $this->assertEquals('%contao.image.valid_extensions%', $definition->getArgument(7));
    }

    /**
     * Tests the contao.image.image_sizes service.
     */
    public function testImageSizes()
    {
        $this->assertTrue($this->container->has('contao.image.image_sizes'));

        $definition = $this->container->getDefinition('contao.image.image_sizes');

        $this->assertEquals(ImageSizes::class, $definition->getClass());
        $this->assertEquals('database_connection', (string) $definition->getArgument(0));
        $this->assertEquals('event_dispatcher', (string) $definition->getArgument(1));
        $this->assertEquals('contao.framework', (string) $definition->getArgument(2));
    }

    /**
     * Tests the contao.image.picture_generator service.
     */
    public function testImagePictureGenerator()
    {
        $this->assertTrue($this->container->has('contao.image.picture_generator'));

        $definition = $this->container->getDefinition('contao.image.picture_generator');

        $this->assertEquals(PictureGenerator::class, $definition->getClass());
        $this->assertEquals('contao.image.resizer', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao.image.picture_factory service.
     */
    public function testImagePictureFactory()
    {
        $this->assertTrue($this->container->has('contao.image.picture_factory'));

        $definition = $this->container->getDefinition('contao.image.picture_factory');

        $this->assertEquals(PictureFactory::class, $definition->getClass());
        $this->assertEquals('contao.image.picture_generator', (string) $definition->getArgument(0));
        $this->assertEquals('contao.image.image_factory', (string) $definition->getArgument(1));
        $this->assertEquals('contao.framework', (string) $definition->getArgument(2));
        $this->assertEquals('%contao.image.bypass_cache%', (string) $definition->getArgument(3));
        $this->assertEquals('%contao.image.imagine_options%', (string) $definition->getArgument(4));
    }

    /**
     * Tests the contao.framework service.
     */
    public function testFramework()
    {
        $this->assertTrue($this->container->has('contao.framework'));

        $definition = $this->container->getDefinition('contao.framework');

        $this->assertEquals(ContaoFramework::class, $definition->getClass());
        $this->assertEquals('request_stack', (string) $definition->getArgument(0));
        $this->assertEquals('router', (string) $definition->getArgument(1));
        $this->assertEquals('session', (string) $definition->getArgument(2));
        $this->assertEquals('contao.routing.scope_matcher', (string) $definition->getArgument(3));
        $this->assertEquals('%kernel.project_dir%', (string) $definition->getArgument(4));
        $this->assertEquals('%contao.error_level%', (string) $definition->getArgument(5));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[ContainerAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertEquals('setContainer', $methodCalls[0][0]);
    }

    /**
     * Tests the contao.menu.matcher service.
     */
    public function testMenuMatcher()
    {
        $this->assertTrue($this->container->has('contao.menu.matcher'));

        $definition = $this->container->getDefinition('contao.menu.matcher');

        $this->assertEquals(Matcher::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    /**
     * Tests the contao.menu.picker_menu_builder service.
     */
    public function testPickerMenuBuilder()
    {
        $this->assertTrue($this->container->has('contao.menu.picker_menu_builder'));

        $definition = $this->container->getDefinition('contao.menu.picker_menu_builder');

        $this->assertEquals(PickerMenuBuilder::class, $definition->getClass());
        $this->assertEquals('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertEquals('contao.menu.renderer', (string) $definition->getArgument(1));
        $this->assertEquals('router', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('knp_menu.menu_builder', $tags);
        $this->assertEquals('createMenu', $tags['knp_menu.menu_builder'][0]['method']);
        $this->assertEquals('picker', $tags['knp_menu.menu_builder'][0]['alias']);
    }

    /**
     * Tests the contao.menu.page_picker_provider service.
     */
    public function testPagePickerProvider()
    {
        $this->assertTrue($this->container->has('contao.menu.page_picker_provider'));

        $definition = $this->container->getDefinition('contao.menu.page_picker_provider');

        $this->assertEquals(PagePickerProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('router', (string) $definition->getArgument(0));
        $this->assertEquals('request_stack', (string) $definition->getArgument(1));
        $this->assertEquals('security.token_storage', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_menu_provider', $tags);
        $this->assertEquals(192, $tags['contao.picker_menu_provider'][0]['priority']);
    }

    /**
     * Tests the contao.menu.file_picker_provider service.
     */
    public function testFilePickerProvider()
    {
        $this->assertTrue($this->container->has('contao.menu.file_picker_provider'));

        $definition = $this->container->getDefinition('contao.menu.file_picker_provider');

        $this->assertEquals(FilePickerProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('router', (string) $definition->getArgument(0));
        $this->assertEquals('request_stack', (string) $definition->getArgument(1));
        $this->assertEquals('security.token_storage', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_menu_provider', $tags);
        $this->assertEquals(160, $tags['contao.picker_menu_provider'][0]['priority']);
    }

    /**
     * Tests the contao.menu.article_picker_provider service.
     */
    public function testArticlePickerProvider()
    {
        $this->assertTrue($this->container->has('contao.menu.article_picker_provider'));

        $definition = $this->container->getDefinition('contao.menu.article_picker_provider');

        $this->assertEquals(ArticlePickerProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('router', (string) $definition->getArgument(0));
        $this->assertEquals('request_stack', (string) $definition->getArgument(1));
        $this->assertEquals('security.token_storage', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.picker_menu_provider', $tags);
    }

    /**
     * Tests the contao.menu.renderer service.
     */
    public function testMenuRenderer()
    {
        $this->assertTrue($this->container->has('contao.menu.renderer'));

        $definition = $this->container->getDefinition('contao.menu.renderer');

        $this->assertEquals(ListRenderer::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('contao.menu.matcher', (string) $definition->getArgument(0));
    }

    /**
     * Tests the contao.monolog.handler service.
     */
    public function testMonologHandler()
    {
        $this->assertTrue($this->container->has('contao.monolog.handler'));

        $definition = $this->container->getDefinition('contao.monolog.handler');

        $this->assertEquals(ContaoTableHandler::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('debug', (string) $definition->getArgument(0));
        $this->assertEquals(false, (string) $definition->getArgument(1));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[ContainerAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertEquals('setContainer', $methodCalls[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('monolog.logger', $tags);
        $this->assertEquals('contao', $tags['monolog.logger'][0]['channel']);
    }

    /**
     * Tests the contao.monolog.processor service.
     */
    public function testMonologProcessor()
    {
        $this->assertTrue($this->container->has('contao.monolog.processor'));

        $definition = $this->container->getDefinition('contao.monolog.processor');

        $this->assertEquals(ContaoTableProcessor::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('request_stack', (string) $definition->getArgument(0));
        $this->assertEquals('security.token_storage', (string) $definition->getArgument(1));
        $this->assertEquals('contao.routing.scope_matcher', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('monolog.processor', $tags);
    }

    /**
     * Tests the contao.referer_id.manager service.
     */
    public function testRefererIdManager()
    {
        $this->assertTrue($this->container->has('contao.referer_id.manager'));

        $definition = $this->container->getDefinition('contao.referer_id.manager');

        $this->assertEquals(CsrfTokenManager::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('contao.referer_id.token_generator', (string) $definition->getArgument(0));
        $this->assertEquals('security.csrf.token_storage', (string) $definition->getArgument(1));
    }

    /**
     * Tests the contao.referer_id.token_generator service.
     */
    public function testRefererIdTokenGenerator()
    {
        $this->assertTrue($this->container->has('contao.referer_id.token_generator'));

        $definition = $this->container->getDefinition('contao.referer_id.token_generator');

        $this->assertEquals(TokenGenerator::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
    }

    /**
     * Tests the contao.resource_finder service.
     */
    public function testResourceFinder()
    {
        $this->assertTrue($this->container->has('contao.resource_finder'));

        $definition = $this->container->getDefinition('contao.resource_finder');

        $this->assertEquals(ResourceFinder::class, $definition->getClass());
        $this->assertEquals('%contao.resources_paths%', $definition->getArgument(0));
    }

    /**
     * Tests the contao.resource_locator service.
     */
    public function testResourceLocator()
    {
        $this->assertTrue($this->container->has('contao.resource_locator'));

        $definition = $this->container->getDefinition('contao.resource_locator');

        $this->assertEquals(FileLocator::class, $definition->getClass());
        $this->assertEquals('%contao.resources_paths%', $definition->getArgument(0));
    }

    /**
     * Tests the contao.routing.frontend_loader service.
     */
    public function testRoutingFrontendLoader()
    {
        $this->assertTrue($this->container->has('contao.routing.frontend_loader'));

        $definition = $this->container->getDefinition('contao.routing.frontend_loader');

        $this->assertEquals(FrontendLoader::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('%contao.prepend_locale%', $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('routing.loader', $tags);
    }

    /**
     * Tests the contao.routing.url_generator service.
     */
    public function testRoutingUrlGenerator()
    {
        $this->assertTrue($this->container->has('contao.routing.url_generator'));

        $definition = $this->container->getDefinition('contao.routing.url_generator');

        $this->assertEquals(UrlGenerator::class, $definition->getClass());
        $this->assertEquals('router', $definition->getArgument(0));
        $this->assertEquals('contao.framework', $definition->getArgument(1));
        $this->assertEquals('%contao.prepend_locale%', $definition->getArgument(2));
    }

    /**
     * Tests the contao.routing.scope_matcher service.
     */
    public function testRoutingScopeMatcher()
    {
        $this->assertTrue($this->container->has('contao.routing.scope_matcher'));

        $definition = $this->container->getDefinition('contao.routing.scope_matcher');

        $this->assertEquals(ScopeMatcher::class, $definition->getClass());
        $this->assertEquals('contao.routing.backend_matcher', $definition->getArgument(0));
        $this->assertEquals('contao.routing.frontend_matcher', $definition->getArgument(1));
    }

    /**
     * Tests the contao.routing.backend_matcher service.
     */
    public function testRoutingBackendMatcher()
    {
        $this->assertTrue($this->container->has('contao.routing.backend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.backend_matcher');

        $this->assertEquals(RequestMatcher::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());

        $methodCalls = $definition->getMethodCalls();

        $this->assertEquals('matchAttribute', $methodCalls[0][0]);
        $this->assertEquals(['_scope', 'backend'], $methodCalls[0][1]);
    }

    /**
     * Tests the contao.routing.frontend_matcher service.
     */
    public function testRoutingFrontendMatcher()
    {
        $this->assertTrue($this->container->has('contao.routing.frontend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.frontend_matcher');

        $this->assertEquals(RequestMatcher::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());

        $methodCalls = $definition->getMethodCalls();

        $this->assertEquals('matchAttribute', $methodCalls[0][0]);
        $this->assertEquals(['_scope', 'frontend'], $methodCalls[0][1]);
    }

    /**
     * Tests the contao.security.authenticator service.
     */
    public function testSecurityAuthenticator()
    {
        $this->assertTrue($this->container->has('contao.security.authenticator'));

        $definition = $this->container->getDefinition('contao.security.authenticator');

        $this->assertEquals(ContaoAuthenticator::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('contao.routing.scope_matcher', $definition->getArgument(0));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[ContainerAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertEquals('setContainer', $methodCalls[0][0]);
    }

    /**
     * Tests the contao.security.user_provider service.
     */
    public function testSecurityUserProvider()
    {
        $this->assertTrue($this->container->has('contao.security.user_provider'));

        $definition = $this->container->getDefinition('contao.security.user_provider');

        $this->assertEquals(ContaoUserProvider::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('contao.framework', $definition->getArgument(0));
        $this->assertEquals('contao.routing.scope_matcher', $definition->getArgument(1));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        /** @var ChildDefinition $childDefinition */
        $childDefinition = $conditionals[ContainerAwareInterface::class];

        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertEquals('setContainer', $methodCalls[0][0]);
    }

    /**
     * Tests the contao.session.contao_backend service.
     */
    public function testContaoBackendSession()
    {
        $this->assertTrue($this->container->has('contao.session.contao_backend'));

        $definition = $this->container->getDefinition('contao.session.contao_backend');

        $this->assertEquals(ArrayAttributeBag::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('_contao_be_attributes', $definition->getArgument(0));

        $methodCalls = $definition->getMethodCalls();

        $this->assertEquals('setName', $methodCalls[0][0]);
        $this->assertEquals(['contao_backend'], $methodCalls[0][1]);
    }

    /**
     * Tests the contao.session.contao_frontend service.
     */
    public function testContaoFrontendSession()
    {
        $this->assertTrue($this->container->has('contao.session.contao_frontend'));

        $definition = $this->container->getDefinition('contao.session.contao_frontend');

        $this->assertEquals(ArrayAttributeBag::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('_contao_fe_attributes', $definition->getArgument(0));

        $methodCalls = $definition->getMethodCalls();

        $this->assertEquals('setName', $methodCalls[0][0]);
        $this->assertEquals(['contao_frontend'], $methodCalls[0][1]);
    }

    /**
     * Tests the contao.twig.template_extension service.
     */
    public function testTwigTemplateExtension()
    {
        $this->assertTrue($this->container->has('contao.twig.template_extension'));

        $definition = $this->container->getDefinition('contao.twig.template_extension');

        $this->assertEquals(ContaoTemplateExtension::class, $definition->getClass());
        $this->assertFalse($definition->isPublic());
        $this->assertEquals('request_stack', $definition->getArgument(0));
        $this->assertEquals('contao.framework', $definition->getArgument(1));
        $this->assertEquals('contao.routing.scope_matcher', $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('twig.extension', $tags);
    }

    /**
     * Tests the deprecated contao.image.target_path configuration option.
     *
     * @expectedDeprecation Using the contao.image.target_path parameter has been deprecated %s.
     * @group legacy
     */
    public function testImageTargetPath()
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getRootDir(),
                'kernel.root_dir' => $this->getRootDir().'/app',
            ])
        );

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $this->assertEquals(
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

        $this->assertEquals(
            str_replace('/', DIRECTORY_SEPARATOR, $this->getRootDir()).'/my/custom/dir',
            $container->getParameter('contao.image.target_dir')
        );
    }

    /**
     * Returns the command test data.
     *
     * @return array
     */
    public function getCommandTestData()
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
}
