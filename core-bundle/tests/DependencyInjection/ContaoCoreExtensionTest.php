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

use Ausi\SlugGenerator\SlugGenerator;
use Contao\BackendUser;
use Contao\ContentProxy;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Cache\ContaoCacheClearer;
use Contao\CoreBundle\Cache\ContaoCacheWarmer;
use Contao\CoreBundle\Command\AutomatorCommand;
use Contao\CoreBundle\Command\CrawlCommand;
use Contao\CoreBundle\Command\DebugDcaCommand;
use Contao\CoreBundle\Command\FilesyncCommand;
use Contao\CoreBundle\Command\InstallCommand;
use Contao\CoreBundle\Command\SymlinksCommand;
use Contao\CoreBundle\Command\UserPasswordCommand;
use Contao\CoreBundle\Command\VersionCommand;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\BackendController;
use Contao\CoreBundle\Controller\BackendCsvImportController;
use Contao\CoreBundle\Controller\BackendPreviewController;
use Contao\CoreBundle\Controller\BackendPreviewSwitchController;
use Contao\CoreBundle\Controller\FaviconController;
use Contao\CoreBundle\Controller\FrontendController;
use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
use Contao\CoreBundle\Controller\ImagesController;
use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Controller\RobotsTxtController;
use Contao\CoreBundle\Cors\WebsiteRootsConfigProvider;
use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Crawl\Escargot\Subscriber\BrokenLinkCheckerSubscriber;
use Contao\CoreBundle\Crawl\Escargot\Subscriber\SearchIndexSubscriber;
use Contao\CoreBundle\Cron\Cron;
use Contao\CoreBundle\Cron\LegacyCron;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Contao\CoreBundle\DataCollector\ContaoDataCollector;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Doctrine\Schema\DcaSchemaProvider;
use Contao\CoreBundle\EventListener\BackendLocaleListener;
use Contao\CoreBundle\EventListener\BypassMaintenanceListener;
use Contao\CoreBundle\EventListener\ClearSessionDataListener;
use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\EventListener\CsrfTokenCookieSubscriber;
use Contao\CoreBundle\EventListener\DataContainerCallbackListener;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\EventListener\ExceptionConverterListener;
use Contao\CoreBundle\EventListener\InitializeControllerListener;
use Contao\CoreBundle\EventListener\InsecureInstallationListener;
use Contao\CoreBundle\EventListener\InsertTags\AssetListener;
use Contao\CoreBundle\EventListener\InsertTags\TranslationListener;
use Contao\CoreBundle\EventListener\LocaleSubscriber;
use Contao\CoreBundle\EventListener\MakeResponsePrivateListener;
use Contao\CoreBundle\EventListener\Menu\BackendLogoutListener;
use Contao\CoreBundle\EventListener\Menu\BackendMenuListener;
use Contao\CoreBundle\EventListener\Menu\BackendPreviewListener;
use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\EventListener\PrettyErrorScreenListener;
use Contao\CoreBundle\EventListener\PreviewAuthenticationListener;
use Contao\CoreBundle\EventListener\PreviewToolbarListener;
use Contao\CoreBundle\EventListener\PreviewUrlConvertListener;
use Contao\CoreBundle\EventListener\PreviewUrlCreateListener;
use Contao\CoreBundle\EventListener\RefererIdListener;
use Contao\CoreBundle\EventListener\RequestTokenListener;
use Contao\CoreBundle\EventListener\ResponseExceptionListener;
use Contao\CoreBundle\EventListener\RobotsTxtListener;
use Contao\CoreBundle\EventListener\SearchIndexListener;
use Contao\CoreBundle\EventListener\StoreRefererListener;
use Contao\CoreBundle\EventListener\SubrequestCacheSubscriber;
use Contao\CoreBundle\EventListener\SwitchUserListener;
use Contao\CoreBundle\EventListener\TwoFactorFrontendListener;
use Contao\CoreBundle\EventListener\UserSessionListener as EventUserSessionListener;
use Contao\CoreBundle\Fragment\ForwardFragmentRenderer;
use Contao\CoreBundle\Fragment\FragmentHandler;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\HttpKernel\ControllerResolver;
use Contao\CoreBundle\HttpKernel\ModelArgumentResolver;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\CoreBundle\Migration\MigrationCollection;
use Contao\CoreBundle\Migration\Version409\CeAccessMigration;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Picker\ArticlePickerProvider;
use Contao\CoreBundle\Picker\FilePickerProvider;
use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\PickerBuilder;
use Contao\CoreBundle\Picker\TablePickerProvider;
use Contao\CoreBundle\Repository\CronJobRepository;
use Contao\CoreBundle\Repository\RememberMeRepository;
use Contao\CoreBundle\Routing\Enhancer\InputEnhancer;
use Contao\CoreBundle\Routing\FrontendLoader;
use Contao\CoreBundle\Routing\ImagesLoader;
use Contao\CoreBundle\Routing\LegacyRouteProvider;
use Contao\CoreBundle\Routing\Matcher\DomainFilter;
use Contao\CoreBundle\Routing\Matcher\LanguageFilter;
use Contao\CoreBundle\Routing\Matcher\LegacyMatcher;
use Contao\CoreBundle\Routing\Matcher\PublishedFilter;
use Contao\CoreBundle\Routing\Matcher\UrlMatcher;
use Contao\CoreBundle\Routing\Route404Provider;
use Contao\CoreBundle\Routing\RouteProvider;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Contao\CoreBundle\Security\Authentication\AuthenticationEntryPoint;
use Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler;
use Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler;
use Contao\CoreBundle\Security\Authentication\ContaoLoginAuthenticationListener;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\CoreBundle\Security\Authentication\RememberMe\ExpiringTokenBasedRememberMeServices;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\CoreBundle\Security\Logout\LogoutSuccessHandler;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Security\TwoFactor\Provider;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Security\User\UserChecker;
use Contao\CoreBundle\Security\Voter\BackendAccessVoter;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Slug\Slug;
use Contao\CoreBundle\Slug\ValidCharacters;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\DataCollectorTranslator;
use Contao\CoreBundle\Translation\Translator;
use Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension;
use Contao\FrontendUser;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Contao\ModuleProxy;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Renderer\ListRenderer;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Cmf\Component\Routing\DynamicRouter;
use Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher;
use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\ResolvePrivatesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\EventListener\LocaleListener as BaseLocaleListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Http\Firewall;

class ContaoCoreExtensionTest extends TestCase
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getTempDir(),
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

        // Resolve private services (see #949)
        $pass = new ResolvePrivatesPass();
        $pass->process($this->container);
    }

    public function testReturnsTheCorrectAlias(): void
    {
        $extension = new ContaoCoreExtension();

        $this->assertSame('contao', $extension->getAlias());
    }

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

    /**
     * @dataProvider getCommandTestData
     */
    public function testRegistersTheCommands(string $key, string $class, bool $public = false): void
    {
        $this->assertTrue($this->container->has($key));

        $definition = $this->container->getDefinition($key);

        $this->assertSame($class, $definition->getClass());
        $this->assertTrue($definition->isAutoconfigured());

        if ($public) {
            $this->assertTrue($definition->isPublic());
        } else {
            $this->assertTrue($definition->isPrivate());
        }
    }

    public function getCommandTestData(): \Generator
    {
        yield ['contao.command.automator', AutomatorCommand::class];
        yield ['contao.command.crawl', CrawlCommand::class];
        yield ['contao.command.debug_dca', DebugDcaCommand::class];
        yield ['contao.command.filesync', FilesyncCommand::class];
        yield ['contao.command.install', InstallCommand::class, true];
        yield ['contao.command.symlinks', SymlinksCommand::class, true];
        yield ['contao.command.user_password_command', UserPasswordCommand::class];
        yield ['contao.command.version', VersionCommand::class];
    }

    public function testRegistersTheBackendLocaleListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.backend_locale'));

        $definition = $this->container->getDefinition('contao.listener.backend_locale');

        $this->assertSame(BackendLocaleListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('translator'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 7,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheBackendMenuListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.menu.backend_menu'));

        $definition = $this->container->getDefinition('contao.listener.menu.backend_menu');

        $this->assertSame(BackendMenuListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('router'),
                new Reference('request_stack'),
                new Reference('translator'),
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 10,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheBackendLogoutListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.menu.backend_logout'));

        $definition = $this->container->getDefinition('contao.listener.menu.backend_logout');

        $this->assertSame(BackendLogoutListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('router'),
                new Reference('security.logout_url_generator'),
                new Reference('translator'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheBackendPreviewListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.menu.backend_preview'));

        $definition = $this->container->getDefinition('contao.listener.menu.backend_preview');

        $this->assertSame(BackendPreviewListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('router'),
                new Reference('request_stack'),
                new Reference('translator'),
                new Reference('event_dispatcher'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheBypassMaintenanceListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.bypass_maintenance'));

        $definition = $this->container->getDefinition('contao.listener.bypass_maintenance');

        $this->assertSame(BypassMaintenanceListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.security.token_checker'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 6,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheClearSessionDataListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.clear_session_data'));

        $definition = $this->container->getDefinition('contao.listener.clear_session_data');

        $this->assertSame(ClearSessionDataListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => -768,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheCommandSchedulerListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.command_scheduler'));

        $definition = $this->container->getDefinition('contao.listener.command_scheduler');

        $this->assertSame(CommandSchedulerListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference(PsrContainerInterface::class),
                new Reference('contao.framework'),
                new Reference('database_connection'),
                new Reference('%fragment.path%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheCsrfTokenCookieSubscriber(): void
    {
        $this->assertTrue($this->container->has('contao.listener.csrf_token_cookie'));

        $definition = $this->container->getDefinition('contao.listener.csrf_token_cookie');

        $this->assertSame(CsrfTokenCookieSubscriber::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.csrf.token_manager'),
                new Reference('contao.csrf.token_storage'),
                new Reference('%contao.csrf_cookie_prefix%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_subscriber' => [
                    [],
                ],
            ],
            $definition->getTags()
        );

        $this->assertSame(
            [
                'kernel.request' => ['onKernelRequest', 36],
                'kernel.response' => ['onKernelResponse', -832],
            ],
            CsrfTokenCookieSubscriber::getSubscribedEvents()
        );
    }

    public function testRegistersTheDataContainerCallbackListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.data_container_callback'));

        $definition = $this->container->getDefinition('contao.listener.data_container_callback');

        $this->assertSame(DataContainerCallbackListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'contao.hook' => [
                    [
                        'hook' => 'loadDataContainer',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheCustomElementTemplateOptionsListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.element_template_options'));

        $definition = $this->container->getDefinition('contao.listener.element_template_options');

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('request_stack'),
                'ce_',
                ContentProxy::class,
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.callback' => [
                    [
                        'table' => 'tl_content',
                        'target' => 'fields.customTpl.options',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheCustomModuleTemplateOptionsListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.module_template_options'));

        $definition = $this->container->getDefinition('contao.listener.module_template_options');

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('request_stack'),
                'mod_',
                ModuleProxy::class,
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.callback' => [
                    [
                        'table' => 'tl_module',
                        'target' => 'fields.customTpl.options',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheDoctrineSchemaListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.doctrine_schema'));

        $definition = $this->container->getDefinition('contao.listener.doctrine_schema');

        $this->assertSame(DoctrineSchemaListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.doctrine.schema_provider'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'doctrine.event_listener' => [
                    [
                        'event' => 'postGenerateSchema',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheExceptionConverterListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.exception_converter'));

        $definition = $this->container->getDefinition('contao.listener.exception_converter');

        $this->assertSame(ExceptionConverterListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 96,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheInitializeControllerListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.initialize_controller'));

        $definition = $this->container->getDefinition('contao.listener.initialize_controller');

        $this->assertSame(InitializeControllerListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 1000,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheInsecureInstallationListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.insecure_installation'));

        $definition = $this->container->getDefinition('contao.listener.insecure_installation');

        $this->assertSame(InsecureInstallationListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheAssetInsertTagListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.insert_tags.asset'));

        $definition = $this->container->getDefinition('contao.listener.insert_tags.asset');

        $this->assertSame(AssetListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('assets.packages'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.hook' => [
                    [
                        'hook' => 'replaceInsertTags',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheTranslationInsertTagListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.insert_tags.translation'));

        $definition = $this->container->getDefinition('contao.listener.insert_tags.translation');

        $this->assertSame(TranslationListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('translator'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.hook' => [
                    [
                        'hook' => 'replaceInsertTags',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheLocaleSubscriber(): void
    {
        $this->assertTrue($this->container->has('contao.listener.locale'));

        $definition = $this->container->getDefinition('contao.listener.locale');

        $this->assertSame(LocaleSubscriber::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('translator'),
                new Reference('contao.routing.scope_matcher'),
                new Reference('%contao.locales%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_subscriber' => [
                    [],
                ],
            ],
            $definition->getTags()
        );

        $this->assertSame(
            [
                'kernel.request' => [
                    ['onKernelRequest', 20],
                    ['setTranslatorLocale', 100],
                ],
            ],
            LocaleSubscriber::getSubscribedEvents()
        );
    }

    public function testRegistersTheMakeResponsePrivateListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.make_response_private'));

        $definition = $this->container->getDefinition('contao.listener.make_response_private');

        $this->assertSame(MakeResponsePrivateListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );

        $tags = $definition->getTags();

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => -896,
                    ],
                ],
            ],
            $tags
        );

        $priority = $tags['kernel.event_listener'][0]['priority'] ?? 0;

        $mergeHeadersListenerDefinition = $this->container->getDefinition('contao.listener.merge_http_headers');
        $mergeHeadersListenerTags = $mergeHeadersListenerDefinition->getTags();
        $mergeHeadersListenerPriority = $mergeHeadersListenerTags['kernel.event_listener'][0]['priority'] ?? 0;

        // Ensure that the listener is registered after the MergeHeaderListener
        $this->assertTrue($priority < $mergeHeadersListenerPriority);

        $clearSessionDataListenerDefinition = $this->container->getDefinition('contao.listener.clear_session_data');
        $clearSessionDataListenerTags = $clearSessionDataListenerDefinition->getTags();
        $clearSessionDataListenerPriority = $clearSessionDataListenerTags['kernel.event_listener'][0]['priority'] ?? 0;

        // Ensure that the listener is registered after the ClearSessionDataListener
        $this->assertTrue($priority < $clearSessionDataListenerPriority);

        $csrfCookieListenerPriority = CsrfTokenCookieSubscriber::getSubscribedEvents()['kernel.response'][1] ?? 0;

        // Ensure that the listener is registered after the CsrfTokenCookieSubscriber
        $this->assertTrue($priority < (int) $csrfCookieListenerPriority);
    }

    public function testRegistersTheMergeHttpHeadersListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.merge_http_headers'));

        $definition = $this->container->getDefinition('contao.listener.merge_http_headers');

        $this->assertSame(MergeHttpHeadersListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 256,
                    ],
                ],
                'kernel.reset' => [
                    [
                        'method' => 'reset',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersThePrettyErrorScreensListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.pretty_error_screens'));

        $definition = $this->container->getDefinition('contao.listener.pretty_error_screens');

        $this->assertSame(PrettyErrorScreenListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%contao.pretty_error_screens%'),
                new Reference('twig'),
                new Reference('contao.framework'),
                new Reference('security.helper'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => -96,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersThePreviewBarListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.preview_bar'));

        $definition = $this->container->getDefinition('contao.listener.preview_bar');

        $this->assertSame(PreviewToolbarListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.routing.scope_matcher'),
                new Reference('twig'),
                new Reference('router'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersThePreviewUrlCreateListener(): void
    {
        $this->assertTrue($this->container->has(PreviewUrlCreateListener::class));

        $definition = $this->container->getDefinition(PreviewUrlCreateListener::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersThePreviewUrlConvertListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.preview_url_convert'));

        $definition = $this->container->getDefinition('contao.listener.preview_url_convert');

        $this->assertSame(PreviewUrlConvertListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheBackendPreviewController(): void
    {
        $this->assertTrue($this->container->has(BackendPreviewController::class));

        $definition = $this->container->getDefinition(BackendPreviewController::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%contao.preview_script%'),
                new Reference('contao.security.frontend_preview_authenticator'),
                new Reference('event_dispatcher'),
                new Reference('router'),
                new Reference('security.authorization_checker'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheBackendPreviewSwitchController(): void
    {
        $this->assertTrue($this->container->has(BackendPreviewSwitchController::class));

        $definition = $this->container->getDefinition(BackendPreviewSwitchController::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.security.frontend_preview_authenticator'),
                new Reference('contao.security.token_checker'),
                new Reference('database_connection'),
                new Reference('security.helper'),
                new Reference('twig'),
                new Reference('router'),
                new Reference(ContaoCsrfTokenManager::class),
                new Reference('translator'),
                new Reference('%contao.csrf_token_name%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRefererIdListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.referer_id'));

        $definition = $this->container->getDefinition('contao.listener.referer_id');

        $this->assertSame(RefererIdListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.token_generator'),
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 20,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheRequestTokenListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.request_token'));

        $definition = $this->container->getDefinition('contao.listener.request_token');

        $this->assertSame(RequestTokenListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('contao.routing.scope_matcher'),
                new Reference(ContaoCsrfTokenManager::class),
                new Reference('%contao.csrf_token_name%'),
                new Reference('%contao.csrf_cookie_prefix%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 14,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheResponseExceptionListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.response_exception'));

        $definition = $this->container->getDefinition('contao.listener.response_exception');

        $this->assertSame(ResponseExceptionListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 64,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheRobotsTxtListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.robots_txt'));

        $definition = $this->container->getDefinition('contao.listener.robots_txt');

        $this->assertSame(RobotsTxtListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheSearchIndexListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.search_index'));

        $definition = $this->container->getDefinition('contao.listener.search_index');

        $this->assertSame(SearchIndexListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.search.indexer'),
                new Reference('%fragment.path%'),
                SearchIndexListener::FEATURE_INDEX | SearchIndexListener::FEATURE_DELETE,
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheStoreRefererListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.store_referer'));

        $definition = $this->container->getDefinition('contao.listener.store_referer');

        $this->assertSame(StoreRefererListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheSubrequestCacheSubscriber(): void
    {
        $this->assertTrue($this->container->has('contao.listener.subrequest_cache'));

        $definition = $this->container->getDefinition('contao.listener.subrequest_cache');

        $this->assertSame(SubrequestCacheSubscriber::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame([], $definition->getArguments());

        $this->assertSame(
            [
                'kernel.event_subscriber' => [
                    [],
                ],
                'kernel.reset' => [
                    [
                        'method' => 'reset',
                    ],
                ],
            ],
            $definition->getTags()
        );

        $this->assertSame(
            [
                'kernel.request' => ['onKernelRequest', 255],
                'kernel.response' => ['onKernelResponse', -255],
            ],
            SubrequestCacheSubscriber::getSubscribedEvents()
        );
    }

    public function testRegistersTheSwitchUserListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.switch_user'));

        $definition = $this->container->getDefinition('contao.listener.switch_user');

        $this->assertSame(SwitchUserListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.token_storage'),
                new Reference('logger'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheTwoFactorFrontendListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.two_factor.frontend'));

        $definition = $this->container->getDefinition('contao.listener.two_factor.frontend');

        $this->assertSame(TwoFactorFrontendListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('contao.routing.scope_matcher'),
                new Reference('security.token_storage'),
                new Reference('%scheb_two_factor.security_tokens%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheUserSessionListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.user_session'));

        $definition = $this->container->getDefinition('contao.listener.user_session');

        $this->assertSame(EventUserSessionListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('database_connection'),
                new Reference('security.helper'),
                new Reference('contao.routing.scope_matcher'),
                new Reference('event_dispatcher'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheAssetPluginContext(): void
    {
        $this->assertTrue($this->container->has('contao.assets.assets_context'));

        $definition = $this->container->getDefinition('contao.assets.assets_context');

        $this->assertSame(ContaoContext::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('contao.framework'),
                new Reference('staticPlugins'),
                new Reference('%kernel.debug%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheAssetFilesContext(): void
    {
        $this->assertTrue($this->container->has('contao.assets.files_context'));

        $definition = $this->container->getDefinition('contao.assets.files_context');

        $this->assertSame(ContaoContext::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('contao.framework'),
                new Reference('staticFiles'),
                new Reference('%kernel.debug%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheContaoCacheClearer(): void
    {
        $this->assertTrue($this->container->has('contao.cache.clear_internal'));

        $definition = $this->container->getDefinition('contao.cache.clear_internal');

        $this->assertSame(ContaoCacheClearer::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('filesystem'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheContaoCacheWarmer(): void
    {
        $this->assertTrue($this->container->has('contao.cache.warm_internal'));

        $definition = $this->container->getDefinition('contao.cache.warm_internal');

        $this->assertSame(ContaoCacheWarmer::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('filesystem'),
                new Reference('contao.resource_finder'),
                new Reference('contao.resource_locator'),
                new Reference('%kernel.project_dir%'),
                new Reference('database_connection'),
                new Reference('contao.framework'),
                new Reference('%contao.locales%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheBackendController(): void
    {
        $this->assertTrue($this->container->has(BackendController::class));

        $definition = $this->container->getDefinition(BackendController::class);

        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheBackendCsvImportController(): void
    {
        $this->assertTrue($this->container->has(BackendCsvImportController::class));

        $definition = $this->container->getDefinition(BackendCsvImportController::class);

        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('database_connection'),
                new Reference('request_stack'),
                new Reference('translator'),
                new Reference('%kernel.project_dir%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheFaviconController(): void
    {
        $this->assertTrue($this->container->has(FaviconController::class));

        $definition = $this->container->getDefinition(FaviconController::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('%kernel.project_dir%'),
                new Reference('fos_http_cache.http.symfony_response_tagger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'controller.service_arguments' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheFrontendController(): void
    {
        $this->assertTrue($this->container->has(FrontendController::class));

        $definition = $this->container->getDefinition(FrontendController::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'controller.service_arguments' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheFrontendModuleTwoFactorController(): void
    {
        $this->assertTrue($this->container->has(TwoFactorController::class));

        $definition = $this->container->getDefinition(TwoFactorController::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'contao.frontend_module' => [
                    [
                        'category' => 'user',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function tesRegistersTheImagesController(): void
    {
        $this->assertTrue($this->container->has(ImagesController::class));

        $definition = $this->container->getDefinition(ImagesController::class);

        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.image.image_factory'),
                new Reference('contao.image.resizer'),
                new Reference('%contao.image.target_dir%'),
                new Reference('filesystem'),
            ],
            $definition->getArguments()
        );
    }

    public function tesRegistersTheInsertTagsController(): void
    {
        $this->assertTrue($this->container->has(InsertTagsController::class));

        $definition = $this->container->getDefinition(InsertTagsController::class);

        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRobotsTxtController(): void
    {
        $this->assertTrue($this->container->has(RobotsTxtController::class));

        $definition = $this->container->getDefinition(RobotsTxtController::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('event_dispatcher'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'controller.service_arguments' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheControllerResolver(): void
    {
        $this->assertTrue($this->container->has('contao.controller_resolver'));

        $definition = $this->container->getDefinition('contao.controller_resolver');

        $this->assertSame(ControllerResolver::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.controller_resolver.inner'),
                new Reference('contao.fragment.registry'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheCorsWebsiteRootsConfigProvider(): void
    {
        $this->assertTrue($this->container->has('contao.cors.website_roots_config_provider'));

        $definition = $this->container->getDefinition('contao.cors.website_roots_config_provider');

        $this->assertSame(WebsiteRootsConfigProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('database_connection'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'nelmio_cors.options_provider' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheEscargotFactory(): void
    {
        $this->assertTrue($this->container->has('contao.crawl.escargot_factory'));

        $definition = $this->container->getDefinition('contao.crawl.escargot_factory');

        $this->assertSame(Factory::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('database_connection'),
                new Reference('contao.framework'),
                [],
                [],
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheEscargotBrokenLinkCheckerSubscriber(): void
    {
        $this->assertTrue($this->container->has('contao.crawl.escargot_subscriber.broken_link_checker'));

        $definition = $this->container->getDefinition('contao.crawl.escargot_subscriber.broken_link_checker');

        $this->assertSame(BrokenLinkCheckerSubscriber::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertEquals(
            [
                new Reference('translator'),
            ],
            $definition->getArguments()
        );
        $this->assertSame(
            [
                'contao.escargot_subscriber' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheEscargotSearchIndexSubscriber(): void
    {
        $this->assertTrue($this->container->has('contao.crawl.escargot_subscriber.search_index'));

        $definition = $this->container->getDefinition('contao.crawl.escargot_subscriber.search_index');

        $this->assertSame(SearchIndexSubscriber::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.search.indexer'),
                new Reference('translator'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.escargot_subscriber' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheCronService(): void
    {
        $this->assertTrue($this->container->has(Cron::class));

        $definition = $this->container->getDefinition(Cron::class);

        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new ServiceClosureArgument(new Reference(CronJobRepository::class)),
                new ServiceClosureArgument(new Reference('doctrine.orm.entity_manager')),
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheLegacyCronService(): void
    {
        $this->assertTrue($this->container->has(LegacyCron::class));

        $definition = $this->container->getDefinition(LegacyCron::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheCsrfTokenManager(): void
    {
        $this->assertTrue($this->container->has(ContaoCsrfTokenManager::class));

        $definition = $this->container->getDefinition(ContaoCsrfTokenManager::class);

        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                '%contao.csrf_cookie_prefix%',
                new Reference('security.csrf.token_generator'),
                new Reference('contao.csrf.token_storage'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheDeprecatedCsrfTokenManager(): void
    {
        $this->assertTrue($this->container->has('contao.csrf.token_manager'));

        $alias = $this->container->getAlias('contao.csrf.token_manager');

        $this->assertSame(ContaoCsrfTokenManager::class, (string) $alias);
        $this->assertTrue($alias->isPublic());
        $this->assertFalse($alias->isDeprecated());
    }

    public function testRegistersTheCsrfTokenStorage(): void
    {
        $this->assertTrue($this->container->has('contao.csrf.token_storage'));

        $definition = $this->container->getDefinition('contao.csrf.token_storage');

        $this->assertSame(MemoryTokenStorage::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'kernel.reset' => [
                    [
                        'method' => 'reset',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheDataCollector(): void
    {
        $this->assertTrue($this->container->has('contao.data_collector'));

        $definition = $this->container->getDefinition('contao.data_collector');

        $this->assertSame(ContaoDataCollector::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                'data_collector' => [
                    [
                        'template' => '@ContaoCore/Collector/contao.html.twig',
                        'id' => 'contao',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheDoctrineSchemaProvider(): void
    {
        $this->assertTrue($this->container->has('contao.doctrine.schema_provider'));

        $definition = $this->container->getDefinition('contao.doctrine.schema_provider');

        $this->assertSame(DcaSchemaProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('doctrine'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheFragmentHandler(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.handler'));

        $definition = $this->container->getDefinition('contao.fragment.handler');

        $this->assertSame(FragmentHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('fragment.handler', $definition->getDecoratedService()[0]);

        $this->assertEquals(
            [
                null,
                new Reference('contao.fragment.handler.inner'),
                new Reference('request_stack'),
                new Reference('contao.fragment.registry'),
                new Reference('contao.fragment.pre_handlers'),
                new Reference('%kernel.debug%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheFragmentPreHandlers(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.pre_handlers'));

        $definition = $this->container->getDefinition('contao.fragment.pre_handlers');

        $this->assertSame(ServiceLocator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                [],
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheFragmentRegistry(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.registry'));

        $definition = $this->container->getDefinition('contao.fragment.registry');

        $this->assertSame(FragmentRegistry::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheFragmentRendererForward(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.forward'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.forward');

        $this->assertSame(ForwardFragmentRenderer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('http_kernel'),
                new Reference('event_dispatcher'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                [
                    'setFragmentPath',
                    ['%fragment.path%'],
                ],
            ],
            $definition->getMethodCalls()
        );

        $this->assertSame(
            [
                'kernel.fragment_renderer' => [
                    [
                        'alias' => 'forward',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheContaoFramework(): void
    {
        $this->assertTrue($this->container->has('contao.framework'));

        $definition = $this->container->getDefinition('contao.framework');

        $this->assertSame(ContaoFramework::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('contao.routing.scope_matcher'),
                new Reference('contao.security.token_checker'),
                new Reference('filesystem'),
                new Reference('%kernel.project_dir%'),
                new Reference('%contao.error_level%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.reset' => [
                    [
                        'method' => 'reset',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheDeferredImageStorage(): void
    {
        $this->assertTrue($this->container->has('contao.image.deferred_image_storage'));

        $definition = $this->container->findDefinition('contao.image.deferred_image_storage');

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%contao.image.target_dir%'),
                new Reference('filesystem', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheImageImagineService(): void
    {
        $this->assertTrue($this->container->has('contao.image.imagine'));

        $definition = $this->container->getAlias('contao.image.imagine');

        $this->assertTrue($definition->isPublic());
    }

    public function testRegistersTheImageImagineSvgService(): void
    {
        $this->assertTrue($this->container->has('contao.image.imagine_svg'));

        $definition = $this->container->getDefinition('contao.image.imagine_svg');

        $this->assertSame(ImagineSvg::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
    }

    public function testRegistersTheImageResizeCalculator(): void
    {
        $this->assertTrue($this->container->has('contao.image.resize_calculator'));

        $definition = $this->container->getDefinition('contao.image.resize_calculator');

        $this->assertSame(ResizeCalculator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheImageResizer(): void
    {
        $this->assertTrue($this->container->has('contao.image.resizer'));

        $definition = $this->container->getDefinition('contao.image.resizer');

        $this->assertSame(LegacyResizer::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('%contao.image.target_dir%'),
                new Reference('contao.image.resize_calculator'),
                new Reference('filesystem'),
                new Reference('contao.image.deferred_image_storage'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheImageFactory(): void
    {
        $this->assertTrue($this->container->has('contao.image.image_factory'));

        $definition = $this->container->getDefinition('contao.image.image_factory');

        $this->assertSame(ImageFactory::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.image.resizer'),
                new Reference('contao.image.imagine'),
                new Reference('contao.image.imagine_svg'),
                new Reference('filesystem'),
                new Reference('contao.framework'),
                new Reference('%contao.image.bypass_cache%'),
                new Reference('%contao.image.imagine_options%'),
                new Reference('%contao.image.valid_extensions%'),
                new Reference('%kernel.project_dir%/%contao.upload_path%'),
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheImageSizesService(): void
    {
        $this->assertTrue($this->container->has('contao.image.image_sizes'));

        $definition = $this->container->getDefinition('contao.image.image_sizes');

        $this->assertSame(ImageSizes::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('database_connection'),
                new Reference('event_dispatcher'),
                new Reference('contao.framework'),
                new Reference('contao.translation.translator'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.reset' => [
                    [
                        'method' => 'reset',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheImagePictureFactory(): void
    {
        $this->assertTrue($this->container->has('contao.image.picture_factory'));

        $definition = $this->container->getDefinition('contao.image.picture_factory');

        $this->assertSame(PictureFactory::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.image.picture_generator'),
                new Reference('contao.image.image_factory'),
                new Reference('contao.framework'),
                new Reference('%contao.image.bypass_cache%'),
                new Reference('%contao.image.imagine_options%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheImagePictureGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.image.picture_generator'));

        $definition = $this->container->getDefinition('contao.image.picture_generator');

        $this->assertSame(PictureGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.image.resizer'),
                new Reference('contao.image.resize_calculator', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheBackendMenuBuilder(): void
    {
        $this->assertTrue($this->container->has('contao.menu.backend_menu_builder'));

        $definition = $this->container->getDefinition('contao.menu.backend_menu_builder');

        $this->assertSame(BackendMenuBuilder::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('knp_menu.factory'),
                new Reference('event_dispatcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheMenuMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.menu.matcher'));

        $definition = $this->container->getDefinition('contao.menu.matcher');

        $this->assertSame(Matcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheMenuRenderer(): void
    {
        $this->assertTrue($this->container->has('contao.menu.renderer'));

        $definition = $this->container->getDefinition('contao.menu.renderer');

        $this->assertSame(ListRenderer::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.menu.matcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheModelArgumentResolver(): void
    {
        $this->assertTrue($this->container->has('contao.model_argument_resolver'));

        $definition = $this->container->getDefinition('contao.model_argument_resolver');

        $this->assertSame(ModelArgumentResolver::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'controller.argument_value_resolver' => [
                    [
                        'priority' => 101,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheMonologHandler(): void
    {
        $this->assertTrue($this->container->has('contao.monolog.handler'));

        $definition = $this->container->getDefinition('contao.monolog.handler');

        $this->assertSame(ContaoTableHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('debug'),
                false,
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'monolog.logger' => [
                    [
                        'channel' => 'contao',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheMonologProcessor(): void
    {
        $this->assertTrue($this->container->has('contao.monolog.processor'));

        $definition = $this->container->getDefinition('contao.monolog.processor');

        $this->assertSame(ContaoTableProcessor::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('security.token_storage'),
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheOptInService(): void
    {
        $this->assertTrue($this->container->has('contao.opt-in'));

        $definition = $this->container->getDefinition('contao.opt-in');

        $this->assertSame(OptIn::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheArticlePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.article_provider'));

        $definition = $this->container->getDefinition('contao.picker.article_provider');

        $this->assertSame(ArticlePickerProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('knp_menu.factory'),
                new Reference('router'),
                new Reference('translator', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                new Reference('security.helper'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersThePickerBuilder(): void
    {
        $this->assertTrue($this->container->has('contao.picker.builder'));

        $definition = $this->container->getDefinition('contao.picker.builder');

        $this->assertSame(PickerBuilder::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('knp_menu.factory'),
                new Reference('router'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheFilePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.file_provider'));

        $definition = $this->container->getDefinition('contao.picker.file_provider');

        $this->assertSame(FilePickerProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('knp_menu.factory'),
                new Reference('router'),
                new Reference('translator'),
                new Reference('security.helper'),
                new Reference('%contao.upload_path%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.picker_provider' => [
                    [
                        'priority' => 160,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersThePagePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.page_provider'));

        $definition = $this->container->getDefinition('contao.picker.page_provider');

        $this->assertSame(PagePickerProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('knp_menu.factory'),
                new Reference('router'),
                new Reference('translator', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                new Reference('security.helper'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.picker_provider' => [
                    [
                        'priority' => 192,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheTablePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.table_provider'));

        $definition = $this->container->getDefinition('contao.picker.table_provider');

        $this->assertSame(TablePickerProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('knp_menu.factory'),
                new Reference('router'),
                new Reference('translator'),
                new Reference('database_connection'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'contao.picker_provider' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheCronJobRepository(): void
    {
        $this->assertTrue($this->container->has(CronJobRepository::class));

        $definition = $this->container->getDefinition(CronJobRepository::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('doctrine'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRememberMeRepository(): void
    {
        $this->assertTrue($this->container->has('contao.repository.remember_me'));

        $definition = $this->container->getDefinition('contao.repository.remember_me');

        $this->assertSame(RememberMeRepository::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('doctrine'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheResourceFinder(): void
    {
        $this->assertTrue($this->container->has('contao.resource_finder'));

        $definition = $this->container->getDefinition('contao.resource_finder');

        $this->assertSame(ResourceFinder::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('%contao.resources_paths%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheResourceLocator(): void
    {
        $this->assertTrue($this->container->has('contao.resource_locator'));

        $definition = $this->container->getDefinition('contao.resource_locator');

        $this->assertSame(FileLocator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('%contao.resources_paths%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingBackendMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.backend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.backend_matcher');

        $this->assertSame(RequestMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                [
                    'matchAttribute',
                    ['_scope', 'backend'],
                ],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testRegistersTheRoutingDomainFilter(): void
    {
        $this->assertTrue($this->container->has('contao.routing.domain_filter'));

        $definition = $this->container->getDefinition('contao.routing.domain_filter');

        $this->assertSame(DomainFilter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheRoutingFinalMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.final_matcher'));

        $definition = $this->container->getDefinition('contao.routing.final_matcher');

        $this->assertSame(UrlMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheRoutingFrontendLoader(): void
    {
        $this->assertTrue($this->container->has('contao.routing.frontend_loader'));

        $definition = $this->container->getDefinition('contao.routing.frontend_loader');

        $this->assertSame(FrontendLoader::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%contao.prepend_locale%'),
                new Reference('%contao.url_suffix%'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'routing.loader' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheRoutingFrontendMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.frontend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.frontend_matcher');

        $this->assertSame(RequestMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                [
                    'matchAttribute',
                    ['_scope', 'frontend'],
                ],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testRegistersTheRoutingImagesLoader(): void
    {
        $this->assertTrue($this->container->has('contao.routing.images_loader'));

        $definition = $this->container->getDefinition('contao.routing.images_loader');

        $this->assertSame(ImagesLoader::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%kernel.project_dir%'),
                new Reference('%contao.image.target_dir%'),
                new Reference('filesystem'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'routing.loader' => [
                    [],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheRoutingInputEnhancer(): void
    {
        $this->assertTrue($this->container->has('contao.routing.input_enhancer'));

        $definition = $this->container->getDefinition('contao.routing.input_enhancer');

        $this->assertSame(InputEnhancer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('%contao.prepend_locale%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingLanguageFilter(): void
    {
        $this->assertTrue($this->container->has('contao.routing.language_filter'));

        $definition = $this->container->getDefinition('contao.routing.language_filter');

        $this->assertSame(LanguageFilter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('%contao.prepend_locale%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingLegacyMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.legacy_matcher'));

        $definition = $this->container->getDefinition('contao.routing.legacy_matcher');

        $this->assertSame(LegacyMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.routing.nested_matcher', $definition->getDecoratedService()[0]);

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('contao.routing.legacy_matcher.inner'),
                new Reference('%contao.url_suffix%'),
                new Reference('%contao.prepend_locale%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingLegacyRouteProvider(): void
    {
        $this->assertTrue($this->container->has('contao.routing.legacy_route_provider'));

        $definition = $this->container->getDefinition('contao.routing.legacy_route_provider');

        $this->assertSame(LegacyRouteProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.routing.frontend_loader'),
                new Reference('contao.routing.legacy_route_provider.inner'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingNestedMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.nested_matcher'));

        $definition = $this->container->getDefinition('contao.routing.nested_matcher');

        $this->assertSame(NestedMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.routing.route_provider'),
                new Reference('contao.routing.final_matcher'),
            ],
            $definition->getArguments()
        );

        $this->assertEquals(
            [
                [
                    'addRouteFilter',
                    [new Reference('contao.routing.domain_filter')],
                ],
                [
                    'addRouteFilter',
                    [new Reference('contao.routing.published_filter')],
                ],
                [
                    'addRouteFilter',
                    [new Reference('contao.routing.language_filter')],
                ],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testRegistersTheRoutingNested404Matcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.nested_404_matcher'));

        $definition = $this->container->getDefinition('contao.routing.nested_404_matcher');

        $this->assertSame(NestedMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.routing.route_404_provider'),
                new Reference('contao.routing.final_matcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingPageRouter(): void
    {
        $this->assertTrue($this->container->has('contao.routing.page_router'));

        $definition = $this->container->getDefinition('contao.routing.page_router');

        $this->assertSame(DynamicRouter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('router.request_context'),
                new Reference('contao.routing.nested_matcher'),
                new Reference('contao.routing.route_generator'),
                '',
                new Reference('event_dispatcher'),
                new Reference('contao.routing.route_provider'),
            ],
            $definition->getArguments()
        );

        $this->assertEquals(
            [
                [
                    'addRouteEnhancer',
                    [new Reference('contao.routing.input_enhancer')],
                ],
            ],
            $definition->getMethodCalls()
        );

        $this->assertSame(
            [
                'router' => [
                    [
                        'priority' => 20,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheRoutingPage404Router(): void
    {
        $this->assertTrue($this->container->has('contao.routing.page_404_router'));

        $definition = $this->container->getDefinition('contao.routing.page_404_router');

        $this->assertSame(DynamicRouter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('router.request_context'),
                new Reference('contao.routing.nested_404_matcher'),
                new Reference('contao.routing.route_generator'),
                null,
                new Reference('event_dispatcher'),
                new Reference('contao.routing.route_404_provider'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingPublishedFilter(): void
    {
        $this->assertTrue($this->container->has('contao.routing.published_filter'));

        $definition = $this->container->getDefinition('contao.routing.published_filter');

        $this->assertSame(PublishedFilter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.security.token_checker'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingRouteGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.routing.route_generator'));

        $definition = $this->container->getDefinition('contao.routing.route_generator');

        $this->assertSame(ProviderBasedGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.routing.route_provider'),
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingRouteProvider(): void
    {
        $this->assertTrue($this->container->has('contao.routing.route_provider'));

        $definition = $this->container->getDefinition('contao.routing.route_provider');

        $this->assertSame(RouteProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('%contao.url_suffix%'),
                new Reference('%contao.prepend_locale%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingRoute404Provider(): void
    {
        $this->assertTrue($this->container->has('contao.routing.route_404_provider'));

        $definition = $this->container->getDefinition('contao.routing.route_404_provider');

        $this->assertSame(Route404Provider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('%contao.prepend_locale%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingScopeMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.scope_matcher'));

        $definition = $this->container->getDefinition('contao.routing.scope_matcher');

        $this->assertSame(ScopeMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.routing.backend_matcher'),
                new Reference('contao.routing.frontend_matcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheRoutingUrlGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.routing.url_generator'));

        $definition = $this->container->getDefinition('contao.routing.url_generator');

        $this->assertSame(UrlGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('router'),
                new Reference('contao.framework'),
                new Reference('%contao.prepend_locale%'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityAuthenticationFailureHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.authentication_failure_handler'));

        $definition = $this->container->getDefinition('contao.security.authentication_failure_handler');

        $this->assertSame(AuthenticationFailureHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityAuthenticationListener(): void
    {
        $this->assertTrue($this->container->has('contao.security.authentication_listener'));

        $definition = $this->container->getDefinition('contao.security.authentication_listener');

        $this->assertSame(ContaoLoginAuthenticationListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.token_storage'),
                new Reference('security.authentication.manager'),
                new Reference('security.authentication.session_strategy'),
                new Reference('security.http_utils'),
                null,
                new Reference('contao.security.authentication_success_handler'),
                new Reference('contao.security.authentication_failure_handler'),
                [],
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
                new Reference('event_dispatcher', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'monolog.logger' => [
                    [
                        'channel' => 'security',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheSecurityAuthenticationProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.authentication_provider'));

        $definition = $this->container->getDefinition('contao.security.authentication_provider');

        $this->assertSame(AuthenticationProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                null,
                null,
                null,
                new Reference('security.encoder_factory'),
                new Reference('contao.framework'),
                null,
                new Reference('scheb_two_factor.authenticated_token_handler'),
                new Reference('scheb_two_factor.authentication_context_factory'),
                new Reference('request_stack'),
                new Reference('contao.security.two_factor.trusted_device_manager'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityAuthenticationSuccessHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.authentication_success_handler'));

        $definition = $this->container->getDefinition('contao.security.authentication_success_handler');

        $this->assertSame(AuthenticationSuccessHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('contao.security.two_factor.trusted_device_manager'),
                new Reference('security.firewall.map'),
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityBackendAccessVoter(): void
    {
        $this->assertTrue($this->container->has('contao.security.backend_access_voter'));

        $definition = $this->container->getDefinition('contao.security.backend_access_voter');

        $this->assertSame(BackendAccessVoter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheSecurityBackendUserProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.backend_user_provider'));

        $definition = $this->container->getDefinition('contao.security.backend_user_provider');

        $this->assertSame(ContaoUserProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('session'),
                BackendUser::class,
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityEntryPoint(): void
    {
        $this->assertTrue($this->container->has('contao.security.entry_point'));

        $definition = $this->container->getDefinition('contao.security.entry_point');

        $this->assertSame(AuthenticationEntryPoint::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('router'),
                new Reference('uri_signer'),
                new Reference('contao.framework'),
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityExpiringTokenBasedRemembermeServices(): void
    {
        $this->assertTrue($this->container->has('contao.security.expiring_token_based_remember_me_services'));

        $definition = $this->container->getDefinition('contao.security.expiring_token_based_remember_me_services');

        $this->assertSame(ExpiringTokenBasedRememberMeServices::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.repository.remember_me'),
                null,
                null,
                null,
                null,
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'monolog.logger' => [
                    [
                        'channel' => 'security',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheSecurityFrontendPreviewAuthenticator(): void
    {
        $this->assertTrue($this->container->has('contao.security.frontend_preview_authenticator'));

        $definition = $this->container->getDefinition('contao.security.frontend_preview_authenticator');

        $this->assertSame(FrontendPreviewAuthenticator::class, $definition->getClass());
        $this->assertFalse($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.helper'),
                new Reference('session'),
                new Reference('contao.security.frontend_user_provider'),
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityFrontendUserProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.frontend_user_provider'));

        $definition = $this->container->getDefinition('contao.security.frontend_user_provider');

        $this->assertSame(ContaoUserProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('session'),
                FrontendUser::class,
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityLogoutHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.logout_handler'));

        $definition = $this->container->getDefinition('contao.security.logout_handler');

        $this->assertSame(LogoutHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityLogoutSuccessHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.logout_success_handler'));

        $definition = $this->container->getDefinition('contao.security.logout_success_handler');

        $this->assertSame(LogoutSuccessHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('security.http_utils'),
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityTokenCheckerWithSimpleRoleVoter(): void
    {
        $this->assertTrue($this->container->has('contao.security.token_checker'));

        $definition = $this->container->getDefinition('contao.security.token_checker');

        $this->assertSame(TokenChecker::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('security.firewall.map'),
                new Reference('security.token_storage'),
                new Reference('session'),
                new Reference('security.authentication.trust_resolver'),
                new Reference('security.access.simple_role_voter'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityTokenCheckerWithRoleHierarchyVoter(): void
    {
        // Populate security configuration
        $this->container->setParameter('security.role_hierarchy.roles', [
            'ROLE_ADMIN' => ['ROLE_USER'],
            'ROLE_SUPER_ADMIN' => ['ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'],
        ]);

        // Reload container
        $extension = new ContaoCoreExtension();
        $extension->load([], $this->container);

        $this->assertTrue($this->container->has('contao.security.token_checker'));

        $definition = $this->container->getDefinition('contao.security.token_checker');

        $this->assertSame(TokenChecker::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('security.firewall.map'),
                new Reference('security.token_storage'),
                new Reference('session'),
                new Reference('security.authentication.trust_resolver'),
                new Reference('security.access.role_hierarchy_voter'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityTwoFactorAuthenticator(): void
    {
        $this->assertTrue($this->container->has('contao.security.two_factor.authenticator'));

        $definition = $this->container->getDefinition('contao.security.two_factor.authenticator');

        $this->assertSame(Authenticator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
    }

    public function testRegistersTheSecurityTwoFactorBackupCodeManager(): void
    {
        $this->assertTrue($this->container->has(BackupCodeManager::class));

        $definition = $this->container->getDefinition(BackupCodeManager::class);

        $this->assertTrue($definition->isPublic());
    }

    public function testRegistersTheSecurityTwoFactorProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.two_factor.provider'));

        $definition = $this->container->getDefinition('contao.security.two_factor.provider');

        $this->assertSame(Provider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.security.two_factor.authenticator'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'scheb_two_factor.provider' => [
                    [
                        'alias' => 'contao',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheSecurityTwoFactorTrustedDeviceManager(): void
    {
        $this->assertTrue($this->container->has('contao.security.two_factor.trusted_device_manager'));

        $definition = $this->container->getDefinition('contao.security.two_factor.trusted_device_manager');

        $this->assertSame(TrustedDeviceManager::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('scheb_two_factor.trusted_token_storage'),
                new Reference('doctrine.orm.entity_manager'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSecurityUserChecker(): void
    {
        $this->assertTrue($this->container->has('contao.security.user_checker'));

        $definition = $this->container->getDefinition('contao.security.user_checker');

        $this->assertSame(UserChecker::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheContaoBackendSession(): void
    {
        $this->assertTrue($this->container->has('contao.session.contao_backend'));

        $definition = $this->container->getDefinition('contao.session.contao_backend');

        $this->assertSame(ArrayAttributeBag::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                '_contao_be_attributes',
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                [
                    'setName',
                    ['contao_backend'],
                ],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testRegistersTheContaoFrontendSession(): void
    {
        $this->assertTrue($this->container->has('contao.session.contao_frontend'));

        $definition = $this->container->getDefinition('contao.session.contao_frontend');

        $this->assertSame(ArrayAttributeBag::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                '_contao_fe_attributes',
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                [
                    'setName',
                    ['contao_frontend'],
                ],
            ],
            $definition->getMethodCalls()
        );
    }

    public function testRegistersTheSlugService(): void
    {
        $this->assertTrue($this->container->has('contao.slug'));

        $definition = $this->container->getDefinition('contao.slug');

        $this->assertSame(Slug::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('contao.slug.generator'),
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSlugGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.slug.generator'));

        $definition = $this->container->getDefinition('contao.slug.generator');

        $this->assertSame(SlugGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertSame(
            [
                ['validChars' => '0-9a-z'],
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheSlugValidCharactersService(): void
    {
        $this->assertTrue($this->container->has('contao.slug.valid_characters'));

        $definition = $this->container->getDefinition('contao.slug.valid_characters');

        $this->assertSame(ValidCharacters::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());

        $this->assertEquals(
            [
                new Reference('event_dispatcher'),
                new Reference('translator'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheTokenGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.token_generator'));

        $definition = $this->container->getDefinition('contao.token_generator');

        $this->assertSame(UriSafeTokenGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertSame(
            [
                48,
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheContaoTranslator(): void
    {
        $this->assertTrue($this->container->has('contao.translation.translator'));

        $definition = $this->container->getDefinition('contao.translation.translator');

        $this->assertSame(Translator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('translator', $definition->getDecoratedService()[0]);

        $this->assertEquals(
            [
                new Reference('contao.translation.translator.inner'),
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheContaoTranslatorDataCollector(): void
    {
        $this->assertTrue($this->container->has('contao.translation.translator.data_collector'));

        $definition = $this->container->getDefinition('contao.translation.translator.data_collector');

        $this->assertSame(DataCollectorTranslator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.translation.translator.data_collector.inner'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.reset' => [
                    [
                        'method' => 'reset',
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    public function testRegistersTheTwigTemplateExtension(): void
    {
        $this->assertTrue($this->container->has('contao.twig.template_extension'));

        $definition = $this->container->getDefinition('contao.twig.template_extension');

        $this->assertSame(ContaoTemplateExtension::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('request_stack'),
                new Reference('contao.framework'),
                new Reference('contao.routing.scope_matcher'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheMigrationCollection(): void
    {
        $this->assertTrue($this->container->has(MigrationCollection::class));

        $definition = $this->container->getDefinition(MigrationCollection::class);

        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheVersion409CeAccessMigration(): void
    {
        $this->assertTrue($this->container->has(CeAccessMigration::class));

        $definition = $this->container->getDefinition(CeAccessMigration::class);

        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('database_connection'),
                new Reference('contao.framework'),
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersThePredefinedImageSizes(): void
    {
        $services = ['contao.image.image_sizes', 'contao.image.image_factory', 'contao.image.picture_factory'];

        $extension = new ContaoCoreExtension();
        $extension->load([], $this->container);

        foreach ($services as $service) {
            $this->assertFalse($this->container->getDefinition($service)->hasMethodCall('setPredefinedSizes'));
        }

        $extension->load(
            [
                'contao' => [
                    'image' => [
                        'sizes' => [
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
            $this->container
        );

        foreach ($services as $service) {
            $this->assertTrue($this->container->getDefinition($service)->hasMethodCall('setPredefinedSizes'));
        }

        $methodCalls = $this->container->getDefinition('contao.image.image_sizes')->getMethodCalls();

        $this->assertSame('setPredefinedSizes', $methodCalls[0][0]);

        $this->assertSame(
            [[
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
                    'formats' => [],
                ],
            ]],
            $methodCalls[0][1]
        );
    }

    public function testSetsTheCrawlOptionsOnTheEscargotFactory(): void
    {
        $extension = new ContaoCoreExtension();
        $extension->load([], $this->container);

        $this->assertTrue($this->container->has('contao.crawl.escargot_factory'));

        $definition = $this->container->getDefinition('contao.crawl.escargot_factory');

        $this->assertEquals(
            [
                new Reference('database_connection'),
                new Reference('contao.framework'),
                [],
                [],
            ],
            $definition->getArguments()
        );

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
            $this->container
        );

        $definition = $this->container->getDefinition('contao.crawl.escargot_factory');

        $this->assertEquals(
            [
                new Reference('database_connection'),
                new Reference('contao.framework'),
                ['https://example.com'],
                ['proxy' => 'http://localhost:7080', 'headers' => ['Foo' => 'Bar']],
            ],
            $definition->getArguments()
        );
    }

    public function testRegistersTheDefaultSearchIndexer(): void
    {
        $extension = new ContaoCoreExtension();
        $extension->load([], $this->container);

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
            $this->container
        );

        $this->assertArrayHasKey(IndexerInterface::class, $this->container->getAutoconfiguredInstanceof());
        $this->assertTrue($this->container->hasDefinition('contao.search.indexer.default'));

        $definition = $this->container->getDefinition('contao.search.indexer.default');

        $this->assertEquals(
            [
                new Reference('contao.framework'),
                new Reference('database_connection'),
                true,
            ],
            $definition->getArguments()
        );
    }

    public function testDoesNotRegisterTheDefaultSearchIndexerIfItIsDisabled(): void
    {
        $extension = new ContaoCoreExtension();
        $extension->load([], $this->container);

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
            $this->container
        );

        // Should still have the interface registered for autoconfiguration
        $this->assertArrayHasKey(IndexerInterface::class, $this->container->getAutoconfiguredInstanceof());
        $this->assertFalse($this->container->hasDefinition('contao.search.indexer.default'));
    }

    public function testSetsTheCorrectFeatureFlagOnTheSearchIndexListener(): void
    {
        $extension = new ContaoCoreExtension();
        $extension->load([], $this->container);

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
            $this->container
        );

        $definition = $this->container->getDefinition('contao.listener.search_index');

        $this->assertSame(SearchIndexListener::class, $definition->getClass());

        $this->assertEquals(
            [
                new Reference('contao.search.indexer'),
                new Reference('%fragment.path%'),
                SearchIndexListener::FEATURE_INDEX,
            ],
            $definition->getArguments()
        );
    }

    public function testRemovesTheSearchIndexListenerIfItIsDisabled(): void
    {
        $extension = new ContaoCoreExtension();
        $extension->load([], $this->container);

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
            $this->container
        );

        $this->assertFalse($this->container->has('contao.listener.search_index'));
    }

    public function testRegistersThePreviewAuthenticationListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.preview_authentication'));

        $definition = $this->container->getDefinition('contao.listener.preview_authentication');

        $this->assertSame(PreviewAuthenticationListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $this->assertEquals(
            [
                new Reference('contao.routing.scope_matcher'),
                new Reference('contao.security.token_checker'),
                new Reference('router'),
                new Reference('uri_signer'),
            ],
            $definition->getArguments()
        );

        $this->assertSame(
            [
                'kernel.event_listener' => [
                    [
                        'priority' => 7,
                    ],
                ],
            ],
            $definition->getTags()
        );
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation Using the "contao.image.target_path" parameter has been deprecated %s.
     */
    public function testRegistersTheImageTargetPath(): void
    {
        $container = new ContainerBuilder(
            new ParameterBag([
                'kernel.debug' => false,
                'kernel.project_dir' => $this->getTempDir(),
                'kernel.default_locale' => 'en',
            ])
        );

        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $this->assertSame($this->getTempDir().'/assets/images', $container->getParameter('contao.image.target_dir'));

        $params = [
            'contao' => [
                'image' => ['target_path' => 'my/custom/dir'],
            ],
        ];

        $extension = new ContaoCoreExtension();
        $extension->load($params, $container);

        $this->assertSame($this->getTempDir().'/my/custom/dir', $container->getParameter('contao.image.target_dir'));
    }
}
