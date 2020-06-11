<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\FrontendModule;

use Contao\BackendUser;
use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
use Contao\CoreBundle\DependencyInjection\Compiler\AddResourcesPathsPass;
use Contao\CoreBundle\DependencyInjection\ContaoCoreExtension;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class TwoFactorControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    public function testReturnsEmptyResponseIfTheUserIsNotFullyAuthenticated(): void
    {
        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->createMock(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller(new Request(), $module, 'main', null, $page);

        $this->assertEmpty($response->getContent());
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $user = $this->createMock(BackendUser::class);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->createMock(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller(new Request(), $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsAResponseIfTheUserIsAFrontendUser(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->createMock(ModuleModel::class);

        $page = $this->mockPageModel();
        $page->enforceTwoFactor = '1';

        $response = $controller(new Request(), $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfTwoFactorAuthenticationIsAlreadyDisabled(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $module = $this->createMock(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller($request, $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRedirectsAfterTwoFactorHasBeenDisabled(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);
        $trustedDeviceManager
            ->expects($this->once())
            ->method('clearTrustedDevices')
            ->with($user)
        ;

        $container->set('contao.security.two_factor.trusted_device_manager', $trustedDeviceManager);

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $module = $this->createMock(ModuleModel::class);

        $page = $this->mockPageModel();
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        /** @var RedirectResponse $response */
        $response = $controller($request, $module, 'main', null, $page);

        $this->assertNull($user->backupCodes);
        $this->assertSame('', $user->useTwoFactor);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost.wip/foobar', $response->getTargetUrl());
    }

    public function testReturnsIfTwoFactorAuthenticationIsAlreadyEnabled(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $module = $this->createMock(ModuleModel::class);

        $page = $this->mockPageModel();
        $page
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testFailsIfTheTwoFactorCodeIsInvalid(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException()),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $module = $this->createMock(ModuleModel::class);

        $page = $this->mockPageModel();
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller($request, $module, 'main', null, $page);
    }

    public function testDoesNotRedirectIfTheTwoFactorCodeIsInvalid(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator($user, false),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $module = $this->createMock(ModuleModel::class);

        $page = $this->mockPageModel();
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller($request, $module, 'main', null, $page);
    }

    public function testRedirectsIfTheTwoFactorCodeIsValid(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '';

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator($user, true),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $module = $this->createMock(ModuleModel::class);

        $page = $this->mockPageModel();
        $page
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $module, 'main', null, $page);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testShowsTheBackupCodes(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_show_backup_codes');

        $module = $this->createMock(ModuleModel::class);
        $page = $this->mockPageModel();

        /** @var RedirectResponse $response */
        $response = $controller($request, $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGeneratesTheBackupCodes(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true)
        );

        /** @var BackupCodeManager&MockObject $backupCodeManager */
        $backupCodeManager = $container->get(BackupCodeManager::class);
        $backupCodeManager
            ->expects($this->once())
            ->method('generateBackupCodes')
            ->with($user)
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_generate_backup_codes');

        $module = $this->createMock(ModuleModel::class);
        $page = $this->mockPageModel();

        /** @var RedirectResponse $response */
        $response = $controller($request, $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testSubscribesToTheRequiredServices(): void
    {
        $services = TwoFactorController::getSubscribedServices();

        $this->assertArrayHasKey('contao.framework', $services);
        $this->assertArrayHasKey('contao.routing.scope_matcher', $services);
        $this->assertArrayHasKey('contao.security.two_factor.authenticator', $services);
        $this->assertArrayHasKey('security.authentication_utils', $services);
        $this->assertArrayHasKey('security.token_storage', $services);
        $this->assertArrayHasKey('translator', $services);
    }

    /**
     * @return Authenticator&MockObject
     */
    private function mockAuthenticator(FrontendUser $user = null, bool $return = null): Authenticator
    {
        $authenticator = $this->createMock(Authenticator::class);

        if ($user instanceof FrontendUser) {
            $authenticator
                ->expects($this->once())
                ->method('validateCode')
                ->with($user, '123456')
                ->willReturn($return)
            ;
        }

        return $authenticator;
    }

    /**
     * @return AuthenticationUtils&MockObject
     */
    private function mockAuthenticationUtils(AuthenticationException $authenticationException = null): AuthenticationUtils
    {
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);

        if ($authenticationException instanceof AuthenticationException) {
            $authenticationUtils
                ->expects($this->once())
                ->method('getLastAuthenticationError')
                ->willReturn($authenticationException)
            ;
        }

        return $authenticationUtils;
    }

    /**
     * @return Security&MockObject
     */
    private function mockSecurityHelper(UserInterface $user = null, bool $isFullyAuthenticated = false): Security
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_FULLY')
            ->willReturn($isFullyAuthenticated)
        ;

        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        return $security;
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPageModel(): PageModel
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->enforceTwoFactor = '';

        return $page;
    }

    private function getContainerWithFrameworkTemplate(string $templateName, Authenticator $authenticator, AuthenticationUtils $authenticationUtils, Security $security, string $projectDir = ''): ContainerBuilder
    {
        $template = $this->createMock(FrontendTemplate::class);
        $template
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        /** @var PageModel&MockObject $adapter */
        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->method('findByPk')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);
        $framework
            ->method('createInstance')
            ->with(FrontendTemplate::class, [$templateName])
            ->willReturn($template)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isFrontendRequest')
            ->willReturn(true)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $backupCodeManager = $this->createMock(BackupCodeManager::class);
        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);

        $finder = new ResourceFinder($this->getFixturesDir().'/vendor/contao/test-bundle/Resources/contao');
        $parameterBag = new ParameterBag(['scheb_two_factor.trusted_device.cookie_name' => 'trusted']);

        $container = new ContainerBuilder($parameterBag);
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.default_locale', 'en');
        $container->setParameter('kernel.cache_dir', $projectDir.'/var/cache');
        $container->setParameter('kernel.project_dir', $projectDir);
        $container->setParameter('kernel.root_dir', $projectDir.'/app');
        $container->setParameter('kernel.bundles', []);
        $container->setParameter('kernel.bundles_metadata', []);

        // Load the default configuration
        $extension = new ContaoCoreExtension();
        $extension->load([], $container);

        $container->set('contao.framework', $framework);
        $container->set('contao.routing.scope_matcher', $scopeMatcher);
        $container->set('translator', $translator);
        $container->set('contao.security.two_factor.authenticator', $authenticator);
        $container->set('contao.security.two_factor.trusted_device_manager', $trustedDeviceManager);
        $container->set('security.authentication_utils', $authenticationUtils);
        $container->set(BackupCodeManager::class, $backupCodeManager);
        $container->set('security.helper', $security);
        $container->set('contao.resource_finder', $finder);
        $container->set('parameter_bag', $parameterBag);

        $pass = new AddResourcesPathsPass();
        $pass->process($container);

        System::setContainer($container);

        return $container;
    }
}
