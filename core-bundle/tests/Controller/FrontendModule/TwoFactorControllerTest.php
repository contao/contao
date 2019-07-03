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
        $tokenStorage = $this->mockTokenStorageWithToken();
        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsIfUserIsNotInstanceOfFrontendUser(): void
    {
        $token = $this->mockToken(TokenInterface::class, true, $this->createMock(BackendUser::class));

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testReturnsResponseIfUserIsAInstanceOfFrontendUser(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, [
            'secret' => '',
            'useTwoFactor' => true,
        ]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testReturnsIfTwoFactorIsAlreadyDisabled(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, [
            'secret' => '',
            'useTwoFactor' => false,
        ]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testRedirectsAfterDisableTwoFactor(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, [
            'secret' => '',
            'useTwoFactor' => true,
        ]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        /** @var RedirectResponse $response */
        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://localhost.wip/foobar', $response->getTargetUrl());
    }

    public function testReturnsIfTwoFactorAlreadyEnabled(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, [
            'secret' => '',
            'useTwoFactor' => true,
        ]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);
        $page
            ->expects($this->any())
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testTwoFactorExceptionHandling(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, [
            'secret' => '',
            'useTwoFactor' => false,
        ]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException());

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $controller($request, $model, 'main', null, $page);
    }

    public function testDoesNotRedirectWithInvalidCode(): void
    {
        /** @var FrontendUser $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, [
            'secret' => '',
            'useTwoFactor' => false,
        ]);

        /** @var TokenInterface $token */
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator($user, false);
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);
        $page
            ->expects($this->exactly(2))
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $controller($request, $model, 'main', null, $page);
    }

    public function testDoesRedirectWithValidCode(): void
    {
        /** @var FrontendUser|MockObject $user */
        $user = $this->mockClassWithProperties(FrontendUser::class, [
            'secret' => '',
            'useTwoFactor' => false,
        ]);

        $user
            ->expects($this->once())
            ->method('save')
        ;

        /** @var TokenInterface $token */
        $token = $this->mockToken(TokenInterface::class, true, $user);

        $tokenStorage = $this->mockTokenStorageWithToken($token);
        $authenticator = $this->mockAuthenticator($user, true);
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);
        $page
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('https://localhost.wip/foobar')
        ;

        $controller = new TwoFactorController();
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor', $tokenStorage, $authenticator, $authenticationUtils));

        $response = $controller($request, $model, 'main', null, $page);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testSubscribedServices(): void
    {
        $services = TwoFactorController::getSubscribedServices();

        $this->assertArrayHasKey('contao.framework', $services);
        $this->assertArrayHasKey('contao.routing.scope_matcher', $services);
        $this->assertArrayHasKey('contao.security.two_factor.authenticator', $services);
        $this->assertArrayHasKey('security.authentication_utils', $services);
        $this->assertArrayHasKey('security.token_storage', $services);
        $this->assertArrayHasKey('translator', $services);
    }

    private function mockToken(string $class, bool $withFrontendUser = false, $user = null)
    {
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

    private function mockAuthenticator(FrontendUser $user = null, $result = null): Authenticator
    {
        $authenticator = $this->createMock(Authenticator::class);

        if ($user instanceof FrontendUser) {
            $authenticator
                ->expects($this->once())
                ->method('validateCode')
                ->with($user, '123456')
                ->willReturn($result)
            ;
        }

        return $authenticator;
    }

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
     * @return \PHPUnit\Framework\MockObject\MockObject|PageModel
     */
    private function mockPageModel(bool $enforceTwoFactor = true)
    {
        return $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => $enforceTwoFactor,
        ]);
    }

    private function mockContainerWithFrameworkTemplate(string $templateName, TokenStorage $tokenStorage, Authenticator $authenticator, AuthenticationUtils $authenticationUtils): ContainerBuilder
    {
        $template = $this->createMock(FrontendTemplate::class);
        $template
            ->expects($this->any())
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
            ->expects($this->any())
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
