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

use Contao\CoreBundle\Controller\BackendPreviewController;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Security\Authentication\FrontendPreviewAuthenticator;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BackendPreviewControllerTest extends TestCase
{
    public function testRedirectsToPreviewEntrypoint(): void
    {
        $controller = new BackendPreviewController(
            $this->mockContaoFramework(),
            'preview.php',
            $this->mockFrontendPreviewAuthenticator(),
            new EventDispatcher(),
            $this->mockRouter(),
            $this->mockAuthorizationChecker()
        );

        /** @var RedirectResponse $response */
        $response = $controller(new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('preview.php', $response->getTargetUrl());
    }

    public function testThrowsAccessDenied(): void
    {
        $controller = new BackendPreviewController(
            $this->mockContaoFramework(),
            'preview.php',
            $this->mockFrontendPreviewAuthenticator(),
            new EventDispatcher(),
            $this->mockRouter(),
            $this->mockAuthorizationChecker(false)
        );

        $response = $controller($this->mockRequest());

        $this->assertSame($response->getStatusCode(), Response::HTTP_FORBIDDEN);
    }

    public function testAuthenticatesWhenUserParameterGiven(): void
    {
        $previewAuthenticator = $this->mockFrontendPreviewAuthenticator();
        $previewAuthenticator
            ->expects($this->once())
            ->method('authenticateFrontendUser')
            ->willReturn(true)
        ;

        $request = $this->mockRequest();
        $request->query->set('user', '9');

        $controller = new BackendPreviewController(
            $this->mockContaoFramework(),
            'preview.php',
            $previewAuthenticator,
            new EventDispatcher(),
            $this->mockRouter(),
            $this->mockAuthorizationChecker()
        );

        $response = $controller($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testDispatchesPreviewUrlConvertEvent(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);
        $dispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(PreviewUrlConvertEvent::class))
        ;

        $controller = new BackendPreviewController(
            $this->mockContaoFramework(),
            'preview.php',
            $this->mockFrontendPreviewAuthenticator(),
            $dispatcher,
            $this->mockRouter(),
            $this->mockAuthorizationChecker()
        );

        /** @var RedirectResponse $response */
        $response = $controller($this->mockRequest());

        $this->assertTrue($response->isRedirection());
    }

    public function testRedirectsToRootPage(): void
    {
        $controller = new BackendPreviewController(
            $this->mockContaoFramework(),
            'preview.php',
            $this->mockFrontendPreviewAuthenticator(),
            new EventDispatcher(),
            $this->mockRouter(),
            $this->mockAuthorizationChecker()
        );

        /** @var RedirectResponse $response */
        $response = $controller($this->mockRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/index.html', $response->getTargetUrl());
    }

    private function mockRequest()
    {
        $request = $this->createMock(Request::class);
        $request
            ->query = new ParameterBag();

        $request
            ->method('getScriptName')
            ->willReturn('preview.php')
        ;

        return $request;
    }

    private function mockRouter()
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->with('contao_root')
            ->willReturn('/index.html')
        ;

        return $router;
    }

    private function mockFrontendPreviewAuthenticator(bool $granted = true)
    {
        return $this->createMock(FrontendPreviewAuthenticator::class);
    }

    private function mockAuthorizationChecker(bool $granted = true)
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturn($granted)
        ;

        return $authorizationChecker;
    }
}
