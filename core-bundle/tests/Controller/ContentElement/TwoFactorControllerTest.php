<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\BackendUser;
use Contao\ContentModel;
use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\ContentElement\TwoFactorController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Security\TwoFactor\BackupCodeManager;
use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FrontendUser;
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

class TwoFactorControllerTest extends ContentElementTestCase
{
    public function testReturnsIfInBackendScope(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createMock(BackendUser::class), true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator($this->createMock(BackendUser::class)),
            $this->mockAuthenticationUtils(),
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createMock(BackendUser::class), true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator($this->createMock(BackendUser::class)),
            $this->mockAuthenticationUtils(),
        );

        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        // todo render template for real and check output
    }

    public function testReturnsIfTheRequestHasNoPageModel(): void
    {
        $container = $this->getContainerWithFrameworkTemplate($this->createMock(BackendUser::class), true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator($this->createMock(BackendUser::class)),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        // todo render template for real and check output
    }

    public function testReturnsEmptyResponseIfTheUserIsNotFullyAuthenticated(): void
    {
        $container = $this->getContainerWithFrameworkTemplate();

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertEmpty($response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        // todo render template for real and check output
    }

    public function testReturnsAResponseIfTheUserIsAFrontendUser(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $request = new Request();

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfTwoFactorAuthenticationIsAlreadyDisabled(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRedirectsAfterTwoFactorHasBeenDisabled(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $trustedDeviceManager = $this->createMock(TrustedDeviceManager::class);
        $trustedDeviceManager
            ->expects($this->once())
            ->method('clearTrustedDevices')
            ->with($user)
        ;

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $trustedDeviceManager,
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $container
            ->get('contao.routing.content_url_generator')
            ->method('generate')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $model, 'main');

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

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $container
            ->get('contao.routing.content_url_generator')
            ->method('generate')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testFailsIfTheTwoFactorCodeIsInvalid(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException()),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $container
            ->get('contao.routing.content_url_generator')
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $model, 'main')->getStatusCode();

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
    }

    public function testDoesNotRedirectIfTheTwoFactorCodeIsInvalid(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = false;

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator($user, false),
            $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException()),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $container
            ->get('contao.routing.content_url_generator')
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $model, 'main')->getStatusCode();

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
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

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator($user, true),
            $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException()),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);
        $page = $this->mockPageModel();

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $container
            ->get('contao.routing.content_url_generator')
            ->method('generate')
            ->with($page, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $model, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost.wip/foobar', $response->getTargetUrl());
    }

    public function testShowsTheBackupCodes(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $this->createMock(BackupCodeManager::class),
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_show_backup_codes');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGeneratesTheBackupCodes(): void
    {
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = true;

        $container = $this->getContainerWithFrameworkTemplate($user, true);

        $backupCodeManager = $container->get('contao.security.two_factor.backup_code_manager');
        $backupCodeManager
            ->expects($this->once())
            ->method('generateBackupCodes')
            ->with($user)
        ;

        $controller = new TwoFactorController(
            $this->getDefaultFramework(),
            $backupCodeManager,
            $this->createMock(TrustedDeviceManager::class),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(),
        );
        $controller->setContainer($container);

        $model = $this->mockClassWithProperties(ContentModel::class);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_generate_backup_codes');

        $response = $controller($request, $model, 'main');

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    private function mockAuthenticator(UserInterface|null $user = null, bool|null $return = null): Authenticator&MockObject
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

    private function mockFrameworkWithTemplate(): ContaoFramework&MockObject
    {
        $template = new FragmentTemplate('two_factor', static fn () => new Response());

        $adapter = $this->mockAdapter(['findById']);
        $adapter
            ->method('findById')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);
        $framework
            ->method('createInstance')
            ->willReturn($template)
        ;

        return $framework;
    }

    private function mockAuthorizationChecker(bool $isFullyAuthenticated = false): AuthorizationCheckerInterface&MockObject
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->with('IS_AUTHENTICATED_FULLY')
            ->willReturn($isFullyAuthenticated)
        ;

        return $authorizationChecker;
    }

    private function mockTokenStorageWithToken(UserInterface|null $user = null): TokenStorageInterface&MockObject
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($user ? new PreAuthenticatedToken($user, 'contao_frontend') : null)
        ;

        return $tokenStorage;
    }

    private function getContainerWithFrameworkTemplate(UserInterface|null $user = null, bool $isFullyAuthenticated = false): ContainerBuilder
    {
        $page = $this->mockPageModel();

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->method('getCurrentPage')
            ->willReturn($page)
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.routing.page_finder', $pageFinder);
        $container->set('contao.framework', $this->mockFrameworkWithTemplate());
        $container->set('contao.routing.content_url_generator', $this->createMock(ContentUrlGenerator::class));
        $container->set('translator', $this->createMock(TranslatorInterface::class));
        $container->set('contao.security.two_factor.trusted_device_manager', $this->createMock(TrustedDeviceManager::class));
        $container->set('security.authorization_checker', $this->mockAuthorizationChecker($isFullyAuthenticated));
        $container->set('security.token_storage', $this->mockTokenStorageWithToken($user));
        $container->set('contao.security.two_factor.backup_code_manager', $this->createMock(BackupCodeManager::class));
        $container->set('contao.cache.tag_manager', $this->createMock(CacheTagManager::class));

        System::setContainer($container);

        return $container;
    }
}
