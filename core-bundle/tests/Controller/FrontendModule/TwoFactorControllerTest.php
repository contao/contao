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
use Contao\CoreBundle\Security\TwoFactor\Authenticator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\Translator;
use Contao\FrontendTemplate;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\System;
use Contao\User;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
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

        System::setContainer($this->mockContainer());
    }

    public function testReturnsIfUserIsNotInstanceOfFrontendUser(): void
    {
        $token = $this->mockToken(TokenInterface::class, true, $this->createMock(BackendUser::class));
        $translator = $this->mockTranslator();
        $router = $this->mockRouter();

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = $this->mockRequest();
        $model = $this->mockModuleModel();
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
        $translator = $this->mockTranslator();
        $router = $this->mockRouter();

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = $this->mockRequest();
        $model = $this->mockModuleModel();
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
        $translator = $this->mockTranslator();
        $router = $this->mockRouter();

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = $this->mockRequest();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $model = $this->mockModuleModel();
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
        $translator = $this->mockTranslator();
        $router = $this->mockRouter();

        $tokenStorage = $this->mockTokenStorageWithToken($token);

        $authenticator = $this->mockAuthenticator();
        $authenticationUtils = $this->mockAuthenticationUtils();

        $request = $this->mockRequest();
        $request->request->set('FORM_SUBMIT', 'tl_two_factor_disable');

        $model = $this->mockModuleModel();
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

    private function mockTranslator(): Translator
    {
        $translator = $this->createMock(Translator::class);

        return $translator;
    }

    private function mockRouter(): Router
    {
        $router = $this->createMock(Router::class);

        return $router;
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

    private function mockAuthenticator(): Authenticator
    {
        $authenticator = $this->createMock(Authenticator::class);

        return $authenticator;
    }

    private function mockAuthenticationUtils(): AuthenticationUtils
    {
        $authenticationUtils = $this->createMock(AuthenticationUtils::class);

        return $authenticationUtils;
    }

    private function mockRequest(): Request
    {
        $request = new Request();

        return $request;
    }

    private function mockModuleModel(): ModuleModel
    {
        $model = $this->mockClassWithProperties(ModuleModel::class);

        return $model;
    }

    private function mockPageModel(bool $enforceTwoFactor = true): PageModel
    {
        $model = $this->mockClassWithProperties(PageModel::class, [
            'enforceTwoFactor' => $enforceTwoFactor,
        ]);

        return $model;
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

        $container = new ContainerBuilder();
        $container->set('contao.framework', $framework);
        $container->set('contao.routing.scope_matcher', $this->mockScopeMatcher());

        return $container;
    }
}
