<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\BackendController;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BackendControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $controller = new BackendController();

        $this->assertInstanceOf('Contao\CoreBundle\Controller\BackendController', $controller);
    }

    public function testRedirectsToTheBackendIfTheUserIsFullyAuthenticatedUponLogin(): void
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn(true)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend')
            ->willReturn('/contao')
        ;

        $container = $this->mockContainer();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('router', $router);

        $controller = new BackendController();
        $controller->setContainer($container);

        /** @var RedirectResponse $response */
        $response = $controller->loginAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('/contao', $response->getTargetUrl());
    }

    public function testRedirectsToTheBackendLoginAfterAUserHasLoggedOut(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login')
            ->willReturn('/contao/login')
        ;

        $container = $this->mockContainer();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('router', $router);

        $controller = new BackendController();
        $controller->setContainer($container);

        $response = $controller->logoutAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('/contao/login', $response->getTargetUrl());
    }

    public function testReturnsAResponseInThePickerActionMethod(): void
    {
        $picker = $this->createMock(PickerInterface::class);
        $picker
            ->method('getCurrentUrl')
            ->willReturn('/foobar')
        ;

        $builder = $this->createMock(PickerBuilderInterface::class);
        $builder
            ->method('create')
            ->willReturn($picker)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturn($builder)
        ;

        $controller = new BackendController();
        $controller->setContainer($container);

        $request = new Request();
        $request->query->set('context', 'page');
        $request->query->set('extras', ['fieldType' => 'radio']);
        $request->query->set('value', '{{link_url::5}}');

        $response = $controller->pickerAction($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);
        $this->assertSame('/foobar', $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
    }

    public function testDoesNotReturnAResponseInThePickerActionMethodIfThePickerExtrasAreInvalid(): void
    {
        $controller = new BackendController();

        $request = new Request();
        $request->query->set('extras', null);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid picker extras');

        $controller->pickerAction($request);
    }

    public function testDoesNotReturnAResponseInThePickerActionMethodIfThePickerContextIsUnsupported(): void
    {
        $builder = $this->createMock(PickerBuilderInterface::class);
        $builder
            ->method('create')
            ->willReturn(null)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturn($builder)
        ;

        $controller = new BackendController();
        $controller->setContainer($container);

        $request = new Request();
        $request->query->set('context', 'invalid');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported picker context');

        $controller->pickerAction($request);
    }

    public function testRedirectsToTheBackendIfTheTwoFactorRouteIsCalledManually(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend')
            ->willReturn('/contao')
        ;

        $container = $this->mockContainer();
        $container->set('router', $router);

        $controller = new BackendController();
        $controller->setContainer($container);

        $response = $controller->twoFactorAuthenticationAction();

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
    }
}
