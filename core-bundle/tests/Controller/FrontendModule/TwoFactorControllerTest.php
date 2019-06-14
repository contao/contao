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
use Contao\CoreBundle\Exception\RedirectResponseException;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
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

    public function testReturnsIfUserIsNotInstanceOfFrontendUser(): void
    {
        $token = $this->mockToken(TokenInterface::class, true, $this->createMock(BackendUser::class));
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
        $controller($request, $model, 'main', null, $page);
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
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel();

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
        $controller($request, $model, 'main', null, $page);
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
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
        $controller($request, $model, 'main', null, $page);
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
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

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

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
        $this->expectException(RedirectResponseException::class);

        $controller($request, $model, 'main', null, $page);
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
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
        $controller($request, $model, 'main', null, $page);
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
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils(new InvalidTwoFactorCodeException());

        $request = new Request();
        $request->request->set('2fa', 'enable');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
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
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator($user, false);
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = new Request();
        $request->request->set('2fa', 'enable');
        $request->request->set('FORM_SUBMIT', 'tl_two_factor');
        $request->request->set('verify', '123456');

        $model = $this->mockClassWithProperties(ModuleModel::class);
        $page = $this->mockPageModel(false);

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
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
        $translator = $this->createMock(Translator::class);
        $router = $this->createMock(RouterInterface::class);

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

        $controller = new TwoFactorController($translator, $router, $tokenStorage, $authenticator, $authenticationUtils);
        $controller->setContainer($this->mockContainerWithFrameworkTemplate('mod_two_factor'));

        $this->assertInstanceOf(TwoFactorController::class, $controller);
        $this->expectException(RedirectResponseException::class);

        $controller($request, $model, 'main', null, $page);
    }

    private function mockToken(string $class, bool $withFrontendUser = false, UserInterface $user = null)
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

    private function mockContainerWithFrameworkTemplate(string $templateName): ContainerBuilder
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

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.scope_matcher', $scopeMatcher);

        return $container;
    }
}
