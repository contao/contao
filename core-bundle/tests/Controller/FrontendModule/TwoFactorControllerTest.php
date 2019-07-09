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
use Contao\CoreBundle\Controller\FrontendModule\TwoFactorController;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\Translator;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\User;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class TwoFactorControllerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());
    }

    public function testReturnsIfNoTokenIsGiven(): void
    {
        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken(),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $model, 'main', null, $this->mockPageModel());

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsIfTheUserIsNotAFrontendUser(): void
    {
        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $this->createMock(BackendUser::class));

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $model, 'main', null, $this->mockPageModel());

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsAResponseIfTheUserIsAFrontendUser(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $response = $controller(new Request(), $model, 'main', null, $this->mockPageModel());

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfTwoFactorAuthenticationIsAlreadyDisabled(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '';

        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $response = $controller($request, $model, 'main', null, $this->mockPageModel());

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRedirectsAfterTwoFactorHasBeenDisabled(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $page = $this->mockPageModel();
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        /** @var RedirectResponse $response */
        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost.wip/foobar', $response->getTargetUrl());
    }

    public function testReturnsIfTwoFactorAuthenticationIsAlreadyEnabled(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '1';

        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $page = $this->mockPageModel();
        $page
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testFailsIfTheTwoFactorCodeIsInvalid(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '';

        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator(),
            $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException())
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $page = $this->mockPageModel();
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller($request, $model, 'main', null, $page);
    }

    public function testDoesNotRedirectIfTheTwoFactorCodeIsInvalid(): void
    {
        /** @var FrontendUser&MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class);
        $user->secret = '';
        $user->useTwoFactor = '';

        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator($user, false),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $page = $this->mockPageModel();
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller($request, $model, 'main', null, $page);
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

        /** @var ModuleModel&MockObject $model */
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $container = $this->getContainerWithFrameworkTemplate(
            'mod_two_factor',
            $this->mockTokenStorageWithToken($token),
            $this->mockAuthenticator($user, true),
            $this->mockAuthenticationUtils()
        );

        $controller = new TwoFactorController();
        $controller->setContainer($container);

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $page = $this->mockPageModel();
        $page
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(RedirectResponse::class, $response);
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
     * @param User&MockObject $user
     *
     * @return TokenInterface&MockObject
     */
    private function mockToken(string $class, bool $withFrontendUser = false, User $user = null): TokenInterface
    {
        /** @var TokenInterface&MockObject $token */
        $token = $this->createMock($class);

        if (null === $user) {
            $user = $this->createMock(FrontendUser::class);
        }

        if ($withFrontendUser) {
            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($user)
            ;
        }

        return $token;
    }

    /**
     * @return TokenStorage&MockObject
     */
    private function mockTokenStorageWithToken(TokenInterface $token = null): TokenStorage
    {
        $tokenStorage = $this->createMock(TokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
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
     * @return PageModel&MockObject
     */
    private function mockPageModel(): PageModel
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->enforceTwoFactor = '';

        return $page;
    }

    private function getContainerWithFrameworkTemplate(string $templateName, TokenStorage $tokenStorage, Authenticator $authenticator, AuthenticationUtils $authenticationUtils): ContainerBuilder
    {
        $template = $this->createMock(FrontendTemplate::class);
        $template
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $adapter = $this->mockAdapter(['findByPk']);
        $adapter
            ->expects($this->atMost(1))
            ->method('findByPk')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $adapter]);
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FrontendTemplate::class, [$templateName])
            ->willReturn($template)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isFrontendRequest')
            ->willReturn(true)
        ;

        $translator = $this->createMock(Translator::class);

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.scope_matcher', $scopeMatcher);
        $container->set('translator', $translator);
        $container->set('security.token_storage', $tokenStorage);
        $container->set('contao.security.two_factor.authenticator', $authenticator);
        $container->set('security.authentication_utils', $authenticationUtils);

        return $container;
    }
}
