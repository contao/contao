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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class TwoFactorControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->createMock(BackendUser::class),
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $response = $controller($request, $module, 'main');

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsIfTheRequestHasNoPageModel(): void
    {
        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $this->createMock(BackendUser::class),
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $request = new Request();

        $response = $controller($request, $module, 'main');

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsEmptyResponseIfTheUserIsNotFullyAuthenticated(): void
    {
        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $response = $controller($request, $module, 'main');

        $this->assertEmpty($response->getContent());
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testReturnsAResponseIfTheUserIsAFrontendUser(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
            $user,
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);

        $page = $this->mockPageModel();
        $page->enforceTwoFactor = true;

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $response = $controller($request, $module, 'main');

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
            $user,
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $response = $controller($request, $module, 'main');

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
            $user,
            true,
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

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $container
            ->get('contao.routing.content_url_generator')
            ->expects($this->exactly(2))
            ->method('generate')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $module, 'main');

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
            $user,
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('2fa', 'enable');

        $container
            ->get('contao.routing.content_url_generator')
            ->method('generate')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $module, 'main');

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
            $user,
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('2fa', 'enable');

        $container
            ->get('contao.routing.content_url_generator')
            ->expects($this->exactly(2))
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller($request, $module, 'main');
    }

    public function testDoesNotRedirectIfTheTwoFactorCodeIsInvalid(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $container = $this->getContainerWithFrameworkTemplate(
            $this->mockAuthenticator($user, false),
            $this->mockAuthenticationUtils(),
            $user,
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $container
            ->get('contao.routing.content_url_generator')
            ->expects($this->exactly(2))
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller($request, $module, 'main');
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
            $user,
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $container
            ->get('contao.routing.content_url_generator')
            ->expects($this->once())
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $module, 'main');

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
            $user,
            true,
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_show_backup_codes');

        $response = $controller($request, $module, 'main');

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
            $user,
            true,
        );

        $backupCodeManager = $container->get('contao.security.two_factor.backup_code_manager');
        $backupCodeManager
            ->expects($this->once())
            ->method('generateBackupCodes')
            ->with($user)
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $module = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_generate_backup_codes');

        $response = $controller($request, $module, 'main');

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

    private function mockPageModel(): PageModel&MockObject
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->enforceTwoFactor = false;

        return $page;
    }

    private function getContainerWithFrameworkTemplate(Authenticator $authenticator, AuthenticationUtils $authenticationUtils, UserInterface|null $user = null, bool $isFullyAuthenticated = false): ContainerBuilder
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

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_FULLY')
            ->willReturn($isFullyAuthenticated)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($user ? new PreAuthenticatedToken($user, 'contao_frontend') : null)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.content_url_generator', $this->createMock(ContentUrlGenerator::class));
        $container->set('translator', $this->createMock(TranslatorInterface::class));
        $container->set('contao.security.two_factor.authenticator', $authenticator);
        $container->set('contao.security.two_factor.trusted_device_manager', $this->createMock(TrustedDeviceManager::class));
        $container->set('security.authentication_utils', $authenticationUtils);
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('contao.security.two_factor.backup_code_manager', $this->createMock(BackupCodeManager::class));
        $container->set('contao.cache.entity_tags', $this->createMock(EntityCacheTags::class));

        System::setContainer($container);

        return $container;
    }
}
