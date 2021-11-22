<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Security;

use Contao\CoreBundle\EventListener\Security\LogoutSuccessListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\HttpUtils;

class LogoutSuccessListenerTest extends TestCase
{
    public function testReturnsIfResponseIsAlreadySet(): void
    {
        $response = new Response();

        $event = new LogoutEvent(new Request(), null);
        $event->setResponse($response);

        $listener = new LogoutSuccessListener($this->createMock(HttpUtils::class), $this->createMock(ScopeMatcher::class));
        $listener($event);

        $this->assertSame($response, $event->getResponse());
    }

    public function testRedirectsToAGivenUrl(): void
    {
        $request = new Request();
        $request->query->set('redirect', 'http://localhost/home');

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $event = new LogoutEvent($request, null);

        $listener = new LogoutSuccessListener($httpUtils, $scopeMatcher);
        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTargetPath(): void
    {
        $request = new Request();
        $request->request->set('_target_path', 'http://localhost/home');

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $event = new LogoutEvent($request, null);

        $listener = new LogoutSuccessListener($httpUtils, $scopeMatcher);
        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTheRefererUrl(): void
    {
        $request = new Request();
        $request->headers->set('Referer', 'http://localhost/home');

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $event = new LogoutEvent($request, null);

        $listener = new LogoutSuccessListener($httpUtils, $scopeMatcher);
        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTheLoginScreenInTheBackend(): void
    {
        $request = new Request();

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'contao_backend_login')
            ->willReturn(new RedirectResponse('contao_backend_login'))
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $event = new LogoutEvent($request, null);

        $listener = new LogoutSuccessListener($httpUtils, $scopeMatcher);
        $listener($event);

        $response = $event->getResponse();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('contao_backend_login', $response->getTargetUrl());
    }
}
