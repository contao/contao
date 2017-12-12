<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Contao\CoreBundle\EventListener\CsrfTokenCookieListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class CsrfTokenCookieListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new CsrfTokenCookieListener($this->createMock(MemoryTokenStorage::class));

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\CsrfTokenCookieListener', $listener);
    }

    public function testInitializesTheStorage(): void
    {
        $request = $this->createMock(Request::class);

        $request->cookies = new ParameterBag([
            'csrf_foo' => 'bar',
            'not_csrf' => 'baz',
        ]);

        $requestEvent = $this->createMock(GetResponseEvent::class);

        $requestEvent
            ->method('isMasterRequest')
            ->willReturn(true)
        ;

        $requestEvent
            ->method('getRequest')
            ->willReturn($request)
        ;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);

        $tokenStorage
            ->expects($this->once())
            ->method('initialize')
            ->with(['foo' => 'bar'])
        ;

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelRequest($requestEvent);
    }

    public function testDoesNotInitializeTheStorageUponSubrequests(): void
    {
        $requestEvent = $this->createMock(GetResponseEvent::class);

        $requestEvent
            ->method('isMasterRequest')
            ->willReturn(false)
        ;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);

        $tokenStorage
            ->expects($this->never())
            ->method('initialize')
        ;

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelRequest($requestEvent);
    }

    public function testAddsTheTokenCookiesToTheResponse(): void
    {
        $request = $this->createMock(Request::class);

        $request
            ->method('isSecure')
            ->willReturn(true)
        ;

        $response = $this->createMock(Response::class);
        $responseEvent = $this->createMock(FilterResponseEvent::class);

        $responseEvent
            ->method('isMasterRequest')
            ->willReturn(true)
        ;

        $responseEvent
            ->method('getRequest')
            ->willReturn($request)
        ;

        $responseEvent
            ->method('getResponse')
            ->willReturn($response)
        ;

        $responseHeaders = $this->createMock(ResponseHeaderBag::class);

        $responseHeaders
            ->expects($this->once())
            ->method('setCookie')
            ->with($this->callback(
                function (Cookie $cookie) {
                    $this->assertSame('csrf_foo', $cookie->getName());
                    $this->assertSame('bar', $cookie->getValue());
                    $this->assertSame('/', $cookie->getPath());
                    $this->assertTrue($cookie->isHttpOnly());
                    $this->assertTrue($cookie->isSecure());
                    $this->assertSame('lax', $cookie->getSameSite());

                    return true;
                }
            ))
        ;

        $response->headers = $responseHeaders;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getUsedTokens')
            ->willReturn(['foo' => 'bar'])
        ;

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    public function testDoesNotAddTheTokenCookiesToTheResponseUponSubrequests(): void
    {
        $responseEvent = $this->createMock(FilterResponseEvent::class);

        $responseEvent
            ->method('isMasterRequest')
            ->willReturn(false)
        ;

        $responseEvent
            ->expects($this->never())
            ->method('getRequest')
        ;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);

        $tokenStorage
            ->expects($this->never())
            ->method('getUsedTokens')
        ;

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }
}
