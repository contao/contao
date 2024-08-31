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

use Contao\BackendMain;
use Contao\CoreBundle\Controller\BackendController;
use Contao\CoreBundle\Picker\PickerBuilderInterface;
use Contao\CoreBundle\Picker\PickerInterface;
use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BackendControllerTest extends TestCase
{
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

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('security.authorization_checker', $authorizationChecker);
        $container->set('router', $router);

        $controller = new BackendController();
        $controller->setContainer($container);

        $response = $controller->loginAction(new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
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

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.framework', $this->mockContaoFramework());
        $container->set('router', $router);

        $controller = new BackendController();
        $controller->setContainer($container);

        $response = $controller->logoutAction();

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

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.picker.builder', $builder);

        $controller = new BackendController();
        $controller->setContainer($container);

        $request = new Request();
        $request->query->set('context', 'page');
        $request->query->set('extras', ['fieldType' => 'radio']);
        $request->query->set('value', '{{link_url::5}}');

        $response = $controller->pickerAction($request);

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

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.picker.builder', $builder);

        $controller = new BackendController();
        $controller->setContainer($container);

        $request = new Request();
        $request->query->set('context', 'invalid');
        $request->query->set('value', '');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Unsupported picker context');

        $controller->pickerAction($request);
    }

    public function testCreatesBackendResponseContext(): void
    {
        $responseContext = new ResponseContext();

        $responseContextFactory = $this->createMock(CoreResponseContextFactory::class);
        $responseContextFactory
            ->expects($this->once())
            ->method('createResponseContext')
            ->willReturn($responseContext)
        ;

        $backendMain = $this->createMock(BackendMain::class);
        $backendMain
            ->expects($this->once())
            ->method('run')
            ->willReturn(new Response())
        ;

        $container = $this->getContainerWithContaoConfiguration();
        $container->set('contao.routing.response_context_factory', $responseContextFactory);
        $container->set('contao.framework', $this->mockContaoFramework([], [BackendMain::class => $backendMain]));

        $controller = new BackendController();
        $controller->setContainer($container);

        $controller->mainAction();

        $this->assertTrue($responseContext->has(HtmlHeadBag::class));
        $this->assertTrue($responseContext->has(HtmlAttributes::class));
    }
}
