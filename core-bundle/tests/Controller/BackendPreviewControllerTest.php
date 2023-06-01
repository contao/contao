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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class BackendPreviewControllerTest extends TestCase
{
    public function testRedirectsToPreviewEntrypoint(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockAuthorizationChecker()
        );

        /** @var RedirectResponse $response */
        $response = $controller(new Request());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/preview.php/', $response->getTargetUrl());
    }

    public function testAddsThePreviewEntrypointAtTheCorrectPosition(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockAuthorizationChecker()
        );

        $request = Request::create('https://localhost/managed-edition/public/contao/preview?page=123');
        $request->server->set('SCRIPT_NAME', '/managed-edition/public/index.php');
        $request->server->set('SCRIPT_FILENAME', '/managed-edition/public/index.php');

        /** @var RedirectResponse $response */
        $response = $controller($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/managed-edition/public/preview.php/contao/preview?page=123', $response->getTargetUrl());
    }

    public function testDeniesAccessIfNotGranted(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockAuthorizationChecker(false)
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        $response = $controller($request);

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testAuthenticatesWhenUserParameterGiven(): void
    {
        $previewAuthenticator = $this->createMock(FrontendPreviewAuthenticator::class);
        $previewAuthenticator
            ->expects($this->once())
            ->method('authenticateFrontendUser')
            ->willReturn(true)
        ;

        $request = Request::create('https://localhost/managed-edition/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/managed-edition/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/managed-edition/preview.php');
        $request->query->set('user', '9');

        $controller = new BackendPreviewController(
            '/preview.php',
            $previewAuthenticator,
            new EventDispatcher(),
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
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            $dispatcher,
            $this->mockAuthorizationChecker()
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        /** @var RedirectResponse $response */
        $response = $controller($request);

        $this->assertTrue($response->isRedirection());
    }

    public function testRedirectsToRootPage(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockAuthorizationChecker()
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        /** @var RedirectResponse $response */
        $response = $controller($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/preview.php/', $response->getTargetUrl());
    }

    /**
     * @return AuthorizationCheckerInterface&MockObject
     */
    private function mockAuthorizationChecker(bool $granted = true): AuthorizationCheckerInterface
    {
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker
            ->method('isGranted')
            ->willReturn($granted)
        ;

        return $authorizationChecker;
    }
}
