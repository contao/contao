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
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;

class BackendPreviewControllerTest extends TestCase
{
    public function testRedirectsToPreviewEntrypoint(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

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
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $request = Request::create('https://localhost/managed-edition/public/contao/preview?page=123');
        $request->server->set('SCRIPT_NAME', '/managed-edition/public/index.php');
        $request->server->set('SCRIPT_FILENAME', '/managed-edition/public/index.php');

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
            $this->mockSecurityHelper(false),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
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
        $request->query->set('user', '9');

        $request->server->set('SCRIPT_NAME', '/managed-edition/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/managed-edition/preview.php');

        $controller = new BackendPreviewController(
            '/preview.php',
            $previewAuthenticator,
            new EventDispatcher(),
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
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
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        $response = $controller($request);

        $this->assertTrue($response->isRedirection());
    }

    public function testRedirectsToRootPage(): void
    {
        $controller = new BackendPreviewController(
            '/preview.php',
            $this->createMock(FrontendPreviewAuthenticator::class),
            new EventDispatcher(),
            $this->mockSecurityHelper(),
            $this->createMock(LoginLinkHandlerInterface::class),
            $this->createMock(UriSigner::class),
        );

        $request = Request::create('https://localhost/preview.php/en/');
        $request->server->set('SCRIPT_NAME', '/preview.php');
        $request->server->set('SCRIPT_FILENAME', '/preview.php');

        $response = $controller($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/preview.php/', $response->getTargetUrl());
    }

    private function mockSecurityHelper(bool $granted = true, UserInterface $user = null): Security&MockObject
    {
        $security = $this->createMock(Security::class);

        $security
            ->method('isGranted')
            ->willReturn($granted)
        ;

        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        //$security
        //    ->method('getToken')
        //    ->willReturn($token)
        //;

        return $security;
    }
}
