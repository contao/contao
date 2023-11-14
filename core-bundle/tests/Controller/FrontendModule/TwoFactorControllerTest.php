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
use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class TwoFactorControllerTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testReturnsEmptyResponseIfTheUserIsNotFullyAuthenticated(): void
    {
        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper(),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller(new Request(), $module, 'main', null, $page);

        $this->assertEmpty($response->getContent());
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $user = $this->createMock(BackendUser::class);

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller(new Request(), $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsAResponseIfTheUserIsAFrontendUser(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);

        $page = $this->mockPageModel();
        $page->enforceTwoFactor = true;

        $response = $controller(new Request(), $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfTwoFactorAuthenticationIsAlreadyDisabled(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller($request, $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRedirectsAfterTwoFactorHasBeenDisabled(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
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

        $module = $this->mockClassWithProperties(ModuleModel::class);

        $page = $this->mockPageModel();
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $module, 'main', null, $page);

        $this->assertNull($user->backupCodes);
        $this->assertFalse($user->useTwoFactor);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost.wip/foobar', $response->getTargetUrl());
    }

    public function testReturnsIfTwoFactorAuthenticationIsAlreadyEnabled(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $module = $this->mockClassWithProperties(ModuleModel::class);

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
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException()),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $module = $this->mockClassWithProperties(ModuleModel::class);

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
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator($user, false),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $module = $this->mockClassWithProperties(ModuleModel::class);

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
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $user
            ->expects($this->once())
            ->method('save')
        ;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator($user, true),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $module = $this->mockClassWithProperties(ModuleModel::class);

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
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_show_backup_codes');

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller($request, $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGeneratesTheBackupCodes(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->mockSecurityHelper($user, true),
        );

        $backupCodeManager = $container->get('contao.security.two_factor.backup_code_manager');
        $backupCodeManager
            ->expects($this->once())
            ->method('generateBackupCodes')
            ->with($user)
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_generate_backup_codes');

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $response = $controller($request, $module, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testSubscribesToTheRequiredServices(): void
    {
        $services = TwoFactorController::getSubscribedServices();

        $this->assertArrayHasKey('contao.framework', $services);
        $this->assertArrayHasKey('contao.routing.scope_matcher', $services);
        $this->assertArrayHasKey('contao.security.two_factor.authenticator', $services);
        $this->assertArrayHasKey('contao.security.two_factor.backup_code_manager', $services);
        $this->assertArrayHasKey('contao.security.two_factor.trusted_device_manager', $services);
        $this->assertArrayHasKey('security.authentication_utils', $services);
        $this->assertArrayHasKey('security.helper', $services);
        $this->assertArrayHasKey('translator', $services);
    }

    private function mockAuthenticator(FrontendUser|null $user = null, bool|null $return = null): Authenticator&MockObject
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

    private function mockAuthenticationUtils(AuthenticationException|null $authenticationException = null): AuthenticationUtils&MockObject
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

    private function mockSecurityHelper(UserInterface|null $user = null, bool $isFullyAuthenticated = false): Security&MockObject
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

    private function mockPageModel(): PageModel&MockObject
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->enforceTwoFactor = false;

        return $page;
    }

    private function getContainerWithFrameworkTemplate(Authenticator $authenticator, AuthenticationUtils $authenticationUtils, Security $security): ContainerBuilder
    {
        $template = $this->createMock(FrontendTemplate::class);
        $template
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->method('findByPk')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);
        $framework
            ->method('createInstance')
            ->with(FrontendTemplate::class, ['mod_two_factor'])
            ->willReturn($template)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('translator', $this->createMock(TranslatorInterface::class));
        $container->set('contao.security.two_factor.authenticator', $authenticator);
        $container->set('contao.security.two_factor.trusted_device_manager', $this->createMock(TrustedDeviceManager::class));
        $container->set('security.authentication_utils', $authenticationUtils);
        $container->set('contao.security.two_factor.backup_code_manager', $this->createMock(BackupCodeManager::class));
        $container->set('security.helper', $security);
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));

        System::setContainer($container);

        return $container;
    }
}
