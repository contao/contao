<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Contao\CoreBundle\EventListener\CsrfTokenCookieListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

class CsrfTokenCookieListenerTest extends TestCase
{
    public function testInitializesTheStorage(): void
    {
        $token = (new UriSafeTokenGenerator())->generateToken();

        $bag = new ParameterBag([
            'csrf_foo' => 'bar',
            'csrf_generated' => $token,
            'not_csrf' => 'baz',
            'csrf_bar' => '"<>!&', // ignore invalid characters
        ]);

        $request = Request::create('https://foobar.com');
        $request->cookies = $bag;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('initialize')
            ->with([
                'foo' => 'bar',
                'generated' => $token,
            ])
        ;

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelRequest($this->getResponseEvent($request));
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
        $bag = new ParameterBag([
            'unrelated-cookie' => 'to-activate-csrf',
        ]);

        $request = Request::create('https://foobar.com');
        $request->cookies = $bag;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getUsedTokens')
            ->willReturn(['foo' => 'bar'])
        ;

        $response = new Response();

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelResponse($this->getFilterResponseEvent($request, $response));

        $cookies = $response->headers->getCookies();

        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];

        $this->assertSame('csrf_foo', $cookie->getName());
        $this->assertSame('bar', $cookie->getValue());
        $this->assertSame(0, $cookie->getExpiresTime());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertSame('lax', $cookie->getSameSite());
    }

    public function testDoesNotAddTheTokenCookiesToTheResponseIfTheyAlreadyExist(): void
    {
        $bag = new ParameterBag([
            'csrf_foo' => 'bar',
            'unrelated-cookie' => 'to-activate-csrf',
        ]);

        $request = Request::create('https://foobar.com');
        $request->cookies = $bag;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getUsedTokens')
            ->willReturn(['foo' => 'bar'])
        ;

        $response = new Response();

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelResponse($this->getFilterResponseEvent($request, $response));

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testRemovesTheTokenCookiesAndReplacesTokenOccurrencesIfNoOtherCookiesArePresent(): void
    {
        $bag = new ParameterBag([
            'csrf_foo' => 'bar',
        ]);

        $request = Request::create('https://foobar.com');
        $request->cookies = $bag;

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize(['tokenName' => 'tokenValue']);
        $tokenStorage->getToken('tokenName');

        $response = new Response(
            '<html><body><form><input name="REQUEST_TOKEN" value="tokenValue"></form></body></html>',
            200,
            ['Content-Type' => 'text/html']
        );

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelResponse($this->getFilterResponseEvent($request, $response));

        $this->assertSame(
            '<html><body><form><input name="REQUEST_TOKEN" value=""></form></body></html>',
            $response->getContent()
        );

        $cookies = $response->headers->getCookies();

        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];

        $this->assertSame('csrf_foo', $cookie->getName());
        $this->assertNull($cookie->getValue());
        $this->assertSame(1, $cookie->getExpiresTime());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->isSecure());
        $this->assertNull($cookie->getSameSite());
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

    public function testDoesNotReplaceTheTokenOccurrencesIfNotAHtmlDocument(): void
    {
        $request = Request::create('https://foobar.com');

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize(['tokenName' => 'tokenValue']);
        $tokenStorage->getToken('tokenName');

        $response = new Response(
            'value="tokenValue"',
            200,
            ['Content-Type' => 'application/octet-stream']
        );

        $listener = new CsrfTokenCookieListener($tokenStorage);
        $listener->onKernelResponse($this->getFilterResponseEvent($request, $response));

        $this->assertSame('value="tokenValue"', $response->getContent());
    }

    /**
     * @return GetResponseEvent&MockObject
     */
    public function getResponseEvent(Request $request = null): GetResponseEvent
    {
        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->method('isMasterRequest')
            ->willReturn(true)
        ;

        $event
            ->method('getRequest')
            ->willReturn($request)
        ;

        return $event;
    }

    /**
     * @return FilterResponseEvent&MockObject
     */
    public function getFilterResponseEvent(Request $request = null, Response $response = null): FilterResponseEvent
    {
        $event = $this->createMock(FilterResponseEvent::class);
        $event
            ->method('isMasterRequest')
            ->willReturn(true)
        ;

        $event
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->method('getResponse')
            ->willReturn($response)
        ;

        return $event;
    }
}
