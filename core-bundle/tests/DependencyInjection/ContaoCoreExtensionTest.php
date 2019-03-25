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
use Contao\CoreBundle\Asset\ContaoContext;
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
use Contao\CoreBundle\EventListener\BackendLocaleListener;
use Contao\CoreBundle\EventListener\BackendMenuListener;
use Contao\CoreBundle\EventListener\BypassMaintenanceListener;
use Contao\CoreBundle\EventListener\ClearSessionDataListener;
use Contao\CoreBundle\EventListener\CommandSchedulerListener;
use Contao\CoreBundle\EventListener\CsrfTokenCookieListener;
use Contao\CoreBundle\EventListener\DataContainerCallbackListener;
use Contao\CoreBundle\EventListener\DoctrineSchemaListener;
use Contao\CoreBundle\EventListener\ExceptionConverterListener;
use Contao\CoreBundle\EventListener\HeaderReplay\PageLayoutListener;
use Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener as HeaderReplayUserSessionListener;
use Contao\CoreBundle\EventListener\InsecureInstallationListener;
use Contao\CoreBundle\EventListener\InsertTags\AssetListener;
use Contao\CoreBundle\EventListener\LocaleListener;
use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\EventListener\PrettyErrorScreenListener;
use Contao\CoreBundle\EventListener\RefererIdListener;
use Contao\CoreBundle\EventListener\ResponseExceptionListener;
use Contao\CoreBundle\EventListener\StoreRefererListener;
use Contao\CoreBundle\EventListener\SwitchUserListener;
use Contao\CoreBundle\EventListener\ToggleViewListener;
use Contao\CoreBundle\EventListener\UserSessionListener as EventUserSessionListener;
use Contao\CoreBundle\Fragment\ForwardFragmentRenderer;
use Contao\CoreBundle\Fragment\FragmentHandler;
use Contao\CoreBundle\Fragment\FragmentRegistry;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\HttpKernel\ControllerResolver;
use Contao\CoreBundle\HttpKernel\ModelArgumentResolver;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\CoreBundle\Image\ImageSizes;
use Contao\CoreBundle\Image\LegacyResizer;
use Contao\CoreBundle\Image\PictureFactory;
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\CoreBundle\Menu\BackendMenuRenderer;
use Contao\CoreBundle\Monolog\ContaoTableHandler;
use Contao\CoreBundle\Monolog\ContaoTableProcessor;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\Picker\ArticlePickerProvider;
use Contao\CoreBundle\Picker\FilePickerProvider;
use Contao\CoreBundle\Picker\PagePickerProvider;
use Contao\CoreBundle\Picker\PickerBuilder;
use Contao\CoreBundle\Referer\TokenGenerator;
use Contao\CoreBundle\Routing\Enhancer\InputEnhancer;
use Contao\CoreBundle\Routing\FrontendLoader;
use Contao\CoreBundle\Routing\LegacyRouteProvider;
use Contao\CoreBundle\Routing\Matcher\DomainFilter;
use Contao\CoreBundle\Routing\Matcher\LegacyMatcher;
use Contao\CoreBundle\Routing\Matcher\UrlMatcher;
use Contao\CoreBundle\Routing\RouteProvider;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\CoreBundle\Security\Authentication\AuthenticationEntryPoint;
use Contao\CoreBundle\Security\Authentication\AuthenticationFailureHandler;
use Contao\CoreBundle\Security\Authentication\AuthenticationSuccessHandler;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Security\Authentication\Provider\AuthenticationProvider;
use Contao\CoreBundle\Security\Authentication\RememberMe\DatabaseTokenProvider;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Security\Logout\LogoutHandler;
use Contao\CoreBundle\Security\Logout\LogoutSuccessHandler;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackendFormRenderer;
use Contao\CoreBundle\Security\TwoFactor\Provider;
use Contao\CoreBundle\Security\User\ContaoUserProvider;
use Contao\CoreBundle\Security\User\UserChecker;
use Contao\CoreBundle\Session\Attribute\ArrayAttributeBag;
use Contao\CoreBundle\Slug\Slug;
use Contao\CoreBundle\Slug\ValidCharacters;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\Translator;
use Contao\CoreBundle\Twig\Extension\ContaoTemplateExtension;
use Contao\FrontendUser;
use Contao\Image\PictureGenerator;
use Contao\Image\ResizeCalculator;
use Contao\ImagineSvg\Imagine as ImagineSvg;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Cmf\Component\Routing\DynamicRouter;
use Symfony\Cmf\Component\Routing\NestedMatcher\NestedMatcher;
use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ServiceLocator;
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
    }

    public function testReturnsTheCorrectAlias(): void
    {
        $extension = new ContaoCoreExtension();

        $this->assertSame('contao', $extension->getAlias());
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

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setFramework', $methodCalls[0][0]);
    }

    /**
     * @return array<int,array<int,string|true>>
     */
    public function getCommandTestData(): array
    {
        return [
            ['contao.command.automator', AutomatorCommand::class],
            ['contao.command.filesync', FilesyncCommand::class],
            ['contao.command.install', InstallCommand::class, true],
            ['contao.command.symlinks', SymlinksCommand::class, true],
            ['contao.command.user_password_command', UserPasswordCommand::class],
            ['contao.command.version', VersionCommand::class],
        ];
    }

    public function testRegistersTheAddToSearchIndexListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.add_to_search_index'));

        $definition = $this->container->getDefinition('contao.listener.add_to_search_index');

        $this->assertSame(AddToSearchIndexListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('%fragment.path%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.terminate', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelTerminate', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheBackendLocaleListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.backend_locale'));

        $definition = $this->container->getDefinition('contao.listener.backend_locale');

        $this->assertSame(BackendLocaleListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('security.token_storage', (string) $definition->getArgument(0));
        $this->assertSame('translator', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(7, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheBackendMenuListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.backend_menu_listener'));

        $definition = $this->container->getDefinition('contao.listener.backend_menu_listener');

        $this->assertSame(BackendMenuListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('security.token_storage', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('contao.backend_menu_build', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onBuild', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheBypassMaintenanceListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.bypass_maintenance'));

        $definition = $this->container->getDefinition('contao.listener.bypass_maintenance');

        $this->assertSame(BypassMaintenanceListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.security.token_checker', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(6, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheClearSessionDataListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.clear_session_data'));

        $definition = $this->container->getDefinition('contao.listener.clear_session_data');

        $this->assertSame(ClearSessionDataListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.response', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelResponse', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(-768, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheCommandSchedulerListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.command_scheduler'));

        $definition = $this->container->getDefinition('contao.listener.command_scheduler');

        $this->assertSame(CommandSchedulerListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('database_connection', (string) $definition->getArgument(1));
        $this->assertSame('%fragment.path%', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.terminate', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelTerminate', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheDataContainerCallbackListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.data_container_callback'));

        $definition = $this->container->getDefinition('contao.listener.data_container_callback');

        $this->assertSame(DataContainerCallbackListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertEmpty($definition->getArguments());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.hook', $tags);
        $this->assertSame('loadDataContainer', $tags['contao.hook'][0]['hook']);
    }

    public function testRegistersTheDoctrineSchemaListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.doctrine_schema'));

        $definition = $this->container->getDefinition('contao.listener.doctrine_schema');

        $this->assertSame(DoctrineSchemaListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPrivate());

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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(0));
        $this->assertSame('contao.security.token_checker', (string) $definition->getArgument(1));

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
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPrivate());

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheAssetInsertTagListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.insert_tags.asset'));

        $definition = $this->container->getDefinition('contao.listener.insert_tags.asset');

        $this->assertSame(AssetListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('assets.packages', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('contao.hook', $tags);
        $this->assertSame('replaceInsertTags', $tags['contao.hook'][0]['hook']);
    }

    public function testRegistersTheLocaleListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.locale'));

        $definition = $this->container->getDefinition('contao.listener.locale');

        $this->assertSame(LocaleListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(0));
        $this->assertSame('%contao.locales%', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(20, $tags['kernel.event_listener'][0]['priority']);
    }

    public function testRegistersTheMergeHttpHeadersListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.merge_http_headers'));

        $definition = $this->container->getDefinition('contao.listener.merge_http_headers');

        $this->assertSame(MergeHttpHeadersListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPrivate());

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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('security.token_storage', (string) $definition->getArgument(0));
        $this->assertSame('security.authentication.trust_resolver', (string) $definition->getArgument(1));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.response', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelResponse', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheSwitchUserListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.switch_user'));

        $definition = $this->container->getDefinition('contao.listener.switch_user');

        $this->assertSame(SwitchUserListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('security.token_storage', (string) $definition->getArgument(0));
        $this->assertSame('logger', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('security.switch_user', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onSwitchUser', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheToggleViewListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.toggle_view'));

        $definition = $this->container->getDefinition('contao.listener.toggle_view');

        $this->assertSame(ToggleViewListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.csrf.token_storage', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
        $this->assertSame(36, $tags['kernel.event_listener'][0]['priority']);
        $this->assertSame('kernel.response', $tags['kernel.event_listener'][1]['event']);
        $this->assertSame('onKernelResponse', $tags['kernel.event_listener'][1]['method']);
    }

    public function testRegistersTheUserSessionListener(): void
    {
        $this->assertTrue($this->container->has('contao.listener.user_session'));

        $definition = $this->container->getDefinition('contao.listener.user_session');

        $this->assertSame(EventUserSessionListener::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('database_connection', (string) $definition->getArgument(0));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(1));
        $this->assertSame('security.authentication.trust_resolver', (string) $definition->getArgument(2));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(3));
        $this->assertSame('event_dispatcher', (string) $definition->getArgument(4));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.event_listener', $tags);
        $this->assertSame('kernel.request', $tags['kernel.event_listener'][0]['event']);
        $this->assertSame('onKernelRequest', $tags['kernel.event_listener'][0]['method']);
    }

    public function testRegistersTheAssetPluginContext(): void
    {
        $this->assertTrue($this->container->has('contao.assets.assets_context'));

        $definition = $this->container->getDefinition('contao.assets.assets_context');

        $this->assertSame(ContaoContext::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('staticPlugins', $definition->getArgument(1));
        $this->assertSame('%kernel.debug%', $definition->getArgument(2));
    }

    public function testRegistersTheAssetFilesContext(): void
    {
        $this->assertTrue($this->container->has('contao.assets.files_context'));

        $definition = $this->container->getDefinition('contao.assets.files_context');

        $this->assertSame(ContaoContext::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('staticFiles', $definition->getArgument(1));
        $this->assertSame('%kernel.debug%', $definition->getArgument(2));
    }

    public function testRegistersTheContaoCacheClearer(): void
    {
        $this->assertTrue($this->container->has('contao.cache.clear_internal'));

        $definition = $this->container->getDefinition('contao.cache.clear_internal');

        $this->assertSame(ContaoCacheClearer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('filesystem', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.cache_clearer', $tags);
    }

    public function testRegistersTheContaoCacheWarmer(): void
    {
        $this->assertTrue($this->container->has('contao.cache.warm_internal'));

        $definition = $this->container->getDefinition('contao.cache.warm_internal');

        $this->assertSame(ContaoCacheWarmer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('database_connection', (string) $definition->getArgument(1));
        $this->assertSame('request_stack', (string) $definition->getArgument(2));
        $this->assertSame('translator', (string) $definition->getArgument(3));
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(4));
    }

    public function tesRegistersThetInsertTagsController(): void
    {
        $this->assertTrue($this->container->has('contao.controller.insert_tags'));

        $definition = $this->container->getDefinition('contao.controller.insert_tags');

        $this->assertSame(InsertTagsController::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    public function testRegistersTheControllerResolver(): void
    {
        $this->assertTrue($this->container->has('contao.controller_resolver'));

        $definition = $this->container->getDefinition('contao.controller_resolver');

        $this->assertSame(ControllerResolver::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.controller_resolver.inner', (string) $definition->getArgument(0));
        $this->assertSame('contao.fragment.registry', (string) $definition->getArgument(1));
    }

    public function testRegistersTheCorsWebsiteRootsConfigProvider(): void
    {
        $this->assertTrue($this->container->has('contao.cors.website_roots_config_provider'));

        $definition = $this->container->getDefinition('contao.cors.website_roots_config_provider');

        $this->assertSame(WebsiteRootsConfigProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('database_connection', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('nelmio_cors.options_provider', $tags);
    }

    public function testRegistersTheCsrfTokenManager(): void
    {
        $this->assertTrue($this->container->has('contao.csrf.token_manager'));

        $definition = $this->container->getDefinition('contao.csrf.token_manager');

        $this->assertSame(CsrfTokenManager::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('security.csrf.token_generator', (string) $definition->getArgument(0));
        $this->assertSame('contao.csrf.token_storage', (string) $definition->getArgument(1));
    }

    public function testRegistersTheCsrfTokenStorage(): void
    {
        $this->assertTrue($this->container->has('contao.csrf.token_storage'));

        $definition = $this->container->getDefinition('contao.csrf.token_storage');

        $this->assertSame(MemoryTokenStorage::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheDataCollector(): void
    {
        $this->assertTrue($this->container->has('contao.data_collector'));

        $definition = $this->container->getDefinition('contao.data_collector');

        $this->assertSame(ContaoDataCollector::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(FrameworkAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[FrameworkAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setFramework', $methodCalls[0][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('data_collector', $tags);
        $this->assertSame('@ContaoCore/Collector/contao.html.twig', $tags['data_collector'][0]['template']);
        $this->assertSame('contao', $tags['data_collector'][0]['id']);
    }

    public function testRegistersTheDoctrineSchemaProvider(): void
    {
        $this->assertTrue($this->container->has('contao.doctrine.schema_provider'));

        $definition = $this->container->getDefinition('contao.doctrine.schema_provider');

        $this->assertSame(DcaSchemaProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('doctrine', (string) $definition->getArgument(1));
    }

    public function testRegistersTheFragmentHandler(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.handler'));

        $definition = $this->container->getDefinition('contao.fragment.handler');

        $this->assertSame(FragmentHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('fragment.handler', $definition->getDecoratedService()[0]);
        $this->assertNull($definition->getArgument(0));
        $this->assertSame('contao.fragment.handler.inner', (string) $definition->getArgument(1));
        $this->assertSame('request_stack', (string) $definition->getArgument(2));
        $this->assertSame('contao.fragment.registry', (string) $definition->getArgument(3));
        $this->assertSame('contao.fragment.pre_handlers', (string) $definition->getArgument(4));
        $this->assertSame('%kernel.debug%', $definition->getArgument(5));
    }

    public function testRegistersTheFragmentPreHandlers(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.pre_handlers'));

        $definition = $this->container->getDefinition('contao.fragment.pre_handlers');

        $this->assertSame(ServiceLocator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame([], $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('container.service_locator', $tags);
    }

    public function testRegistersTheFragmentRegistry(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.registry'));

        $definition = $this->container->getDefinition('contao.fragment.registry');

        $this->assertSame(FragmentRegistry::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheForwardFragmentRenderer(): void
    {
        $this->assertTrue($this->container->has('contao.fragment.renderer.forward'));

        $definition = $this->container->getDefinition('contao.fragment.renderer.forward');

        $this->assertSame(ForwardFragmentRenderer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('http_kernel', (string) $definition->getArgument(0));
        $this->assertSame('event_dispatcher', (string) $definition->getArgument(1));

        $calls = $definition->getMethodCalls();

        $this->assertSame('setFragmentPath', $calls[0][0]);
        $this->assertSame('%fragment.path%', (string) $calls[0][1][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('kernel.fragment_renderer', $tags);
        $this->assertSame('forward', $tags['kernel.fragment_renderer'][0]['alias']);
    }

    public function testRegistersTheContaoFramework(): void
    {
        $this->assertTrue($this->container->has('contao.framework'));

        $definition = $this->container->getDefinition('contao.framework');

        $this->assertSame(ContaoFramework::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(1));
        $this->assertSame('contao.security.token_checker', (string) $definition->getArgument(2));
        $this->assertSame('%kernel.project_dir%', (string) $definition->getArgument(3));
        $this->assertSame('%contao.error_level%', (string) $definition->getArgument(4));

        $conditionals = $definition->getInstanceofConditionals();

        $this->assertArrayHasKey(ContainerAwareInterface::class, $conditionals);

        $childDefinition = $conditionals[ContainerAwareInterface::class];
        $methodCalls = $childDefinition->getMethodCalls();

        $this->assertSame('setContainer', $methodCalls[0][0]);
    }

    public function testRegistersTheImageImagineService(): void
    {
        $this->assertTrue($this->container->has('contao.image.imagine'));

        $definition = $this->container->findDefinition('contao.image.imagine');

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
        $this->assertTrue($definition->isPublic());
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
        $this->assertTrue($definition->isPublic());
        $this->assertSame('database_connection', (string) $definition->getArgument(0));
        $this->assertSame('event_dispatcher', (string) $definition->getArgument(1));
        $this->assertSame('contao.framework', (string) $definition->getArgument(2));
    }

    public function testRegistersTheImagePictureGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.image.picture_generator'));

        $definition = $this->container->getDefinition('contao.image.picture_generator');

        $this->assertSame(PictureGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.image.resizer', (string) $definition->getArgument(0));
        $this->assertSame('contao.image.resize_calculator', (string) $definition->getArgument(1));
    }

    public function testRegistersTheImagePictureFactory(): void
    {
        $this->assertTrue($this->container->has('contao.image.picture_factory'));

        $definition = $this->container->getDefinition('contao.image.picture_factory');

        $this->assertSame(PictureFactory::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.image.picture_generator', (string) $definition->getArgument(0));
        $this->assertSame('contao.image.image_factory', (string) $definition->getArgument(1));
        $this->assertSame('contao.framework', (string) $definition->getArgument(2));
        $this->assertSame('%contao.image.bypass_cache%', (string) $definition->getArgument(3));
        $this->assertSame('%contao.image.imagine_options%', (string) $definition->getArgument(4));
    }

    public function testRegistersTheBackendMenuRenderer(): void
    {
        $this->assertTrue($this->container->has('contao.menu.backend_menu_renderer'));

        $definition = $this->container->getDefinition('contao.menu.backend_menu_renderer');

        $this->assertSame(BackendMenuRenderer::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('twig', (string) $definition->getArgument(0));
    }

    public function testRegistersTheBackendMenuBuilder(): void
    {
        $this->assertTrue($this->container->has('contao.menu.backend_menu_builder'));

        $definition = $this->container->getDefinition('contao.menu.backend_menu_builder');

        $this->assertSame(BackendMenuBuilder::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('event_dispatcher', (string) $definition->getArgument(1));
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
        $this->assertSame('contao.menu.matcher', (string) $definition->getArgument(0));
    }

    public function testRegistersTheModelArgumentResolver(): void
    {
        $this->assertTrue($this->container->has('contao.model_argument_resolver'));

        $definition = $this->container->getDefinition('contao.model_argument_resolver');

        $this->assertSame(ModelArgumentResolver::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(1));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('controller.argument_value_resolver', $tags);
        $this->assertSame(101, $tags['controller.argument_value_resolver'][0]['priority']);
    }

    public function testRegistersTheMonologHandler(): void
    {
        $this->assertTrue($this->container->has('contao.monolog.handler'));

        $definition = $this->container->getDefinition('contao.monolog.handler');

        $this->assertSame(ContaoTableHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('debug', (string) $definition->getArgument(0));
        $this->assertFalse($definition->getArgument(1));

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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('request_stack', (string) $definition->getArgument(0));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(1));
        $this->assertSame('contao.routing.scope_matcher', (string) $definition->getArgument(2));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('monolog.processor', $tags);
    }

    public function testRegistersTheOptInService(): void
    {
        $this->assertTrue($this->container->has('contao.opt-in'));

        $definition = $this->container->getDefinition('contao.opt-in');

        $this->assertSame(OptIn::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
    }

    public function testRegistersThePickerBuilder(): void
    {
        $this->assertTrue($this->container->has('contao.picker.builder'));

        $definition = $this->container->getDefinition('contao.picker.builder');

        $this->assertSame(PickerBuilder::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
    }

    public function testRegistersThePagePickerProvider(): void
    {
        $this->assertTrue($this->container->has('contao.picker.page_provider'));

        $definition = $this->container->getDefinition('contao.picker.page_provider');

        $this->assertSame(PagePickerProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('translator', (string) $definition->getArgument(2));

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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('translator', (string) $definition->getArgument(2));
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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('knp_menu.factory', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
        $this->assertSame('translator', (string) $definition->getArgument(2));

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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.referer_id.token_generator', (string) $definition->getArgument(0));
        $this->assertSame('security.csrf.token_storage', (string) $definition->getArgument(1));
    }

    public function testRegistersTheRefererIdTokenGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.referer_id.token_generator'));

        $definition = $this->container->getDefinition('contao.referer_id.token_generator');

        $this->assertSame(TokenGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
    }

    public function testRegistersTheResourceFinder(): void
    {
        $this->assertTrue($this->container->has('contao.resource_finder'));

        $definition = $this->container->getDefinition('contao.resource_finder');

        $this->assertSame(ResourceFinder::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('%contao.resources_paths%', $definition->getArgument(0));
    }

    public function testRegistersTheResourceLocator(): void
    {
        $this->assertTrue($this->container->has('contao.resource_locator'));

        $definition = $this->container->getDefinition('contao.resource_locator');

        $this->assertSame(FileLocator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('%contao.resources_paths%', $definition->getArgument(0));
    }

    public function testRegistersTheRoutingBackendMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.backend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.backend_matcher');

        $this->assertSame(RequestMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('matchAttribute', $methodCalls[0][0]);
        $this->assertSame(['_scope', 'backend'], $methodCalls[0][1]);
    }

    public function testRegistersTheRoutingDomainFilter(): void
    {
        $this->assertTrue($this->container->has('contao.routing.domain_filter'));

        $definition = $this->container->getDefinition('contao.routing.domain_filter');

        $this->assertSame(DomainFilter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertEmpty($definition->getArguments());
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
        $this->assertSame('%contao.prepend_locale%', (string) $definition->getArgument(0));

        $tags = $definition->getTags();

        $this->assertArrayHasKey('routing.loader', $tags);
    }

    public function testRegistersTheRoutingFrontendMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.frontend_matcher'));

        $definition = $this->container->getDefinition('contao.routing.frontend_matcher');

        $this->assertSame(RequestMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('matchAttribute', $methodCalls[0][0]);
        $this->assertSame(['_scope', 'frontend'], $methodCalls[0][1]);
    }

    public function testRegistersTheRoutingInputEnhancer(): void
    {
        $this->assertTrue($this->container->has('contao.routing.input_enhancer'));

        $definition = $this->container->getDefinition('contao.routing.input_enhancer');

        $this->assertSame(InputEnhancer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('%contao.prepend_locale%', (string) $definition->getArgument(1));
    }

    public function testRegistersTheRoutingLegacyMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.legacy_matcher'));

        $definition = $this->container->getDefinition('contao.routing.legacy_matcher');

        $this->assertSame(LegacyMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.routing.nested_matcher', $definition->getDecoratedService()[0]);
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.legacy_matcher.inner', (string) $definition->getArgument(1));
        $this->assertSame('%contao.url_suffix%', (string) $definition->getArgument(2));
        $this->assertSame('%contao.prepend_locale%', (string) $definition->getArgument(3));
    }

    public function testRegistersTheRoutingLegacyRouteProvider(): void
    {
        $this->assertTrue($this->container->has('contao.routing.legacy_route_provider'));

        $definition = $this->container->getDefinition('contao.routing.legacy_route_provider');

        $this->assertSame(LegacyRouteProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.routing.frontend_loader', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.legacy_route_provider.inner', (string) $definition->getArgument(1));
    }

    public function testRegistersTheRoutingNestedMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.nested_matcher'));

        $definition = $this->container->getDefinition('contao.routing.nested_matcher');

        $this->assertSame(NestedMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.routing.route_provider', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.final_matcher', (string) $definition->getArgument(1));

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('addRouteFilter', $methodCalls[0][0]);
        $this->assertSame('contao.routing.domain_filter', (string) $methodCalls[0][1][0]);
    }

    public function testRegistersTheRoutingPageRouter(): void
    {
        $this->assertTrue($this->container->has('contao.routing.page_router'));

        $definition = $this->container->getDefinition('contao.routing.page_router');

        $this->assertSame(DynamicRouter::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('router.request_context', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.nested_matcher', (string) $definition->getArgument(1));
        $this->assertSame('contao.routing.route_generator', (string) $definition->getArgument(2));
        $this->assertSame('', (string) $definition->getArgument(3));
        $this->assertSame('event_dispatcher', (string) $definition->getArgument(4));
        $this->assertSame('contao.routing.route_provider', (string) $definition->getArgument(5));

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('addRouteEnhancer', $methodCalls[0][0]);
        $this->assertSame('contao.routing.input_enhancer', (string) $methodCalls[0][1][0]);

        $tags = $definition->getTags();

        $this->assertArrayHasKey('router', $tags);
        $this->assertSame(20, $tags['router'][0]['priority']);
    }

    public function testRegistersTheRoutingRouteGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.routing.route_generator'));

        $definition = $this->container->getDefinition('contao.routing.route_generator');

        $this->assertSame(ProviderBasedGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.routing.route_provider', (string) $definition->getArgument(0));
        $this->assertSame('logger', (string) $definition->getArgument(1));
    }

    public function testRegistersTheRoutingRouteProvider(): void
    {
        $this->assertTrue($this->container->has('contao.routing.route_provider'));

        $definition = $this->container->getDefinition('contao.routing.route_provider');

        $this->assertSame(RouteProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('database_connection', (string) $definition->getArgument(1));
        $this->assertSame('%contao.url_suffix%', (string) $definition->getArgument(2));
        $this->assertSame('%contao.prepend_locale%', (string) $definition->getArgument(3));
    }

    public function testRegistersTheRoutingScopeMatcher(): void
    {
        $this->assertTrue($this->container->has('contao.routing.scope_matcher'));

        $definition = $this->container->getDefinition('contao.routing.scope_matcher');

        $this->assertSame(ScopeMatcher::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.routing.backend_matcher', (string) $definition->getArgument(0));
        $this->assertSame('contao.routing.frontend_matcher', (string) $definition->getArgument(1));
    }

    public function testRegistersTheRoutingUrlGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.routing.url_generator'));

        $definition = $this->container->getDefinition('contao.routing.url_generator');

        $this->assertSame(UrlGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('router', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));
        $this->assertSame('%contao.prepend_locale%', (string) $definition->getArgument(2));
    }

    public function testRegistersTheSecurityAuthenticationFailureHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.authentication_failure_handler'));

        $definition = $this->container->getDefinition('contao.security.authentication_failure_handler');

        $this->assertSame(AuthenticationFailureHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('http_kernel', (string) $definition->getArgument(0));
        $this->assertSame('security.http_utils', (string) $definition->getArgument(1));
        $this->assertSame([], $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
    }

    public function testRegistersTheSecurityAuthenticationProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.authentication_provider'));

        $definition = $this->container->getDefinition('contao.security.authentication_provider');

        $this->assertSame(AuthenticationProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertNull($definition->getArgument(0));
        $this->assertNull($definition->getArgument(1));
        $this->assertNull($definition->getArgument(2));
        $this->assertSame('security.encoder_factory', (string) $definition->getArgument(3));
        $this->assertSame('contao.framework', (string) $definition->getArgument(4));
    }

    public function testRegistersTheSecurityAuthenticationSuccessHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.authentication_success_handler'));

        $definition = $this->container->getDefinition('contao.security.authentication_success_handler');

        $this->assertSame(AuthenticationSuccessHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('security.http_utils', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));
        $this->assertSame('logger', (string) $definition->getArgument(2));
    }

    public function testRegistersTheSecurityDatabaseTokenProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.database_token_provider'));

        $definition = $this->container->getDefinition('contao.security.database_token_provider');

        $this->assertSame(DatabaseTokenProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('database_connection', (string) $definition->getArgument(0));
        $this->assertSame('%kernel.secret%', (string) $definition->getArgument(1));
    }

    public function testRegistersTheSecurityBackendUserProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.backend_user_provider'));

        $definition = $this->container->getDefinition('contao.security.backend_user_provider');

        $this->assertSame(ContaoUserProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('session', (string) $definition->getArgument(1));
        $this->assertSame(BackendUser::class, (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
    }

    public function testRegistersTheSecurityEntryPoint(): void
    {
        $this->assertTrue($this->container->has('contao.security.entry_point'));

        $definition = $this->container->getDefinition('contao.security.entry_point');

        $this->assertSame(AuthenticationEntryPoint::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('security.http_utils', (string) $definition->getArgument(0));
        $this->assertSame('router', (string) $definition->getArgument(1));
    }

    public function testRegistersTheSecurityFrontendPreviewAuthenticator(): void
    {
        $this->assertTrue($this->container->has('contao.security.frontend_preview_authenticator'));

        $definition = $this->container->getDefinition('contao.security.frontend_preview_authenticator');

        $this->assertSame(FrontendPreviewAuthenticator::class, $definition->getClass());
        $this->assertFalse($definition->isPrivate());
        $this->assertSame('session', (string) $definition->getArgument(0));
        $this->assertSame('security.token_storage', (string) $definition->getArgument(1));
        $this->assertSame('contao.security.frontend_user_provider', (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
    }

    public function testRegistersTheSecurityFrontendUserProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.frontend_user_provider'));

        $definition = $this->container->getDefinition('contao.security.frontend_user_provider');

        $this->assertSame(ContaoUserProvider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('session', (string) $definition->getArgument(1));
        $this->assertSame(FrontendUser::class, (string) $definition->getArgument(2));
        $this->assertSame('logger', (string) $definition->getArgument(3));
    }

    public function testRegistersTheSecurityLogoutSuccessHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.logout_success_handler'));

        $definition = $this->container->getDefinition('contao.security.logout_success_handler');

        $this->assertSame(LogoutSuccessHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('security.http_utils', (string) $definition->getArgument(0));
    }

    public function testRegistersTheSecurityLogoutHandler(): void
    {
        $this->assertTrue($this->container->has('contao.security.logout_handler'));

        $definition = $this->container->getDefinition('contao.security.logout_handler');

        $this->assertSame(LogoutHandler::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('logger', (string) $definition->getArgument(1));
    }

    public function testRegistersTheSecurityTokenChecker(): void
    {
        $this->assertTrue($this->container->has('contao.security.token_checker'));

        $definition = $this->container->getDefinition('contao.security.token_checker');

        $this->assertSame(TokenChecker::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('session', (string) $definition->getArgument(0));
        $this->assertSame('security.authentication.trust_resolver', (string) $definition->getArgument(1));
    }

    public function testRegistersTheSecurityTwoFactorAuthenticator(): void
    {
        $this->assertTrue($this->container->has('contao.security.two_factor.authenticator'));

        $definition = $this->container->getDefinition('contao.security.two_factor.authenticator');

        $this->assertSame(Authenticator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
    }

    public function testRegistersTheSecurityTwoFactorBackendFormRenderer(): void
    {
        $this->assertTrue($this->container->has('contao.security.two_factor.backend_form_renderer'));

        $definition = $this->container->getDefinition('contao.security.two_factor.backend_form_renderer');

        $this->assertSame(BackendFormRenderer::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('router', (string) $definition->getArgument(0));
    }

    public function testRegistersTheSecurityTwoFactorBackendProvider(): void
    {
        $this->assertTrue($this->container->has('contao.security.two_factor.backend_provider'));

        $definition = $this->container->getDefinition('contao.security.two_factor.backend_provider');

        $this->assertSame(Provider::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.security.two_factor.authenticator', (string) $definition->getArgument(0));
        $this->assertSame('contao.security.two_factor.backend_form_renderer', (string) $definition->getArgument(1));
    }

    public function testRegistersTheSecurityUserChecker(): void
    {
        $this->assertTrue($this->container->has('contao.security.user_checker'));

        $definition = $this->container->getDefinition('contao.security.user_checker');

        $this->assertSame(UserChecker::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('contao.framework', (string) $definition->getArgument(0));
        $this->assertSame('logger', (string) $definition->getArgument(1));
    }

    public function testRegistersTheContaoBackendSession(): void
    {
        $this->assertTrue($this->container->has('contao.session.contao_backend'));

        $definition = $this->container->getDefinition('contao.session.contao_backend');

        $this->assertSame(ArrayAttributeBag::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
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
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('_contao_fe_attributes', (string) $definition->getArgument(0));

        $methodCalls = $definition->getMethodCalls();

        $this->assertSame('setName', $methodCalls[0][0]);
        $this->assertSame(['contao_frontend'], $methodCalls[0][1]);
    }

    public function testRegistersTheSlugService(): void
    {
        $this->assertTrue($this->container->has('contao.slug'));

        $definition = $this->container->getDefinition('contao.slug');

        $this->assertSame(Slug::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('contao.slug.generator', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));
    }

    public function testRegistersTheSlugGenerator(): void
    {
        $this->assertTrue($this->container->has('contao.slug.generator'));

        $definition = $this->container->getDefinition('contao.slug.generator');

        $this->assertSame(SlugGenerator::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame(['validChars' => '0-9a-z'], $definition->getArgument(0));
    }

    public function testRegistersTheSlugValidCharactersService(): void
    {
        $this->assertTrue($this->container->has('contao.slug.valid_characters'));

        $definition = $this->container->getDefinition('contao.slug.valid_characters');

        $this->assertSame(ValidCharacters::class, $definition->getClass());
        $this->assertTrue($definition->isPublic());
        $this->assertSame('event_dispatcher', (string) $definition->getArgument(0));
        $this->assertSame('translator', (string) $definition->getArgument(1));
    }

    public function testRegistersTheContaoTranslator(): void
    {
        $this->assertTrue($this->container->has('contao.translation.translator'));

        $definition = $this->container->getDefinition('contao.translation.translator');

        $this->assertSame(Translator::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
        $this->assertSame('translator', $definition->getDecoratedService()[0]);
        $this->assertSame('contao.translation.translator.inner', (string) $definition->getArgument(0));
        $this->assertSame('contao.framework', (string) $definition->getArgument(1));
    }

    public function testRegistersTheTwigTemplateExtension(): void
    {
        $this->assertTrue($this->container->has('contao.twig.template_extension'));

        $definition = $this->container->getDefinition('contao.twig.template_extension');

        $this->assertSame(ContaoTemplateExtension::class, $definition->getClass());
        $this->assertTrue($definition->isPrivate());
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
