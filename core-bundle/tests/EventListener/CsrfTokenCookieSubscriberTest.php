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
use Contao\CoreBundle\EventListener\CsrfTokenCookieSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

class CsrfTokenCookieSubscriberTest extends TestCase
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

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelRequest($this->getRequestEvent($request));
    }

    public function testDoesNotInitializeTheStorageUponSubrequests(): void
    {
        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent
            ->method('isMasterRequest')
            ->willReturn(false)
        ;

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('initialize')
        ;

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
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

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

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

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

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
            ['Content-Type' => 'text/html', 'Content-Length' => 1234]
        );

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertSame(
            '<html><body><form><input name="REQUEST_TOKEN" value=""></form></body></html>',
            $response->getContent()
        );

        $this->assertFalse($response->headers->has('Content-Length'));

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

    public function testDoesNotChangeTheResponseIfNoTokensArePresent(): void
    {
        $bag = new ParameterBag([
            'csrf_foo' => 'bar',
        ]);

        $request = Request::create('https://foobar.com');
        $request->cookies = $bag;

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize([]);

        $response = new Response(
            '<html><body><form><input name="REQUEST_TOKEN" value="tokenValue"></form></body></html>',
            200,
            ['Content-Type' => 'text/html', 'Content-Length' => 1234]
        );

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertSame(
            '<html><body><form><input name="REQUEST_TOKEN" value="tokenValue"></form></body></html>',
            $response->getContent()
        );

        $this->assertTrue($response->headers->has('Content-Length'));
    }

    public function testDoesNotChangeTheResponseIfTokensAreNotFound(): void
    {
        $bag = new ParameterBag([
            'csrf_foo' => 'bar',
        ]);

        $request = Request::create('https://foobar.com');
        $request->cookies = $bag;

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize(['tokenName' => 'tokenValue']);
        $tokenStorage->getToken('tokenName');

        $response = $this->createMock(Response::class);
        $response
            ->expects($this->once())
            ->method('isSuccessful')
            ->willReturn(true)
        ;

        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(
                '<html><body><form><input name="REQUEST_TOKEN" value=""></form></body></html>'
            )
        ;

        $response
            ->expects($this->never())
            ->method('setContent')
        ;

        $response->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));
    }

    public function testDoesNotAddTheTokenCookiesToTheResponseUponSubrequests(): void
    {
        $responseEvent = $this->createMock(ResponseEvent::class);
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

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
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

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertSame('value="tokenValue"', $response->getContent());
    }

    /**
     * @return RequestEvent&MockObject
     */
    public function getRequestEvent(Request $request = null): RequestEvent
    {
        $event = $this->createMock(RequestEvent::class);
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
     * @return ResponseEvent&MockObject
     */
    public function getResponseEvent(Request $request = null, Response $response = null): ResponseEvent
    {
        $event = $this->createMock(ResponseEvent::class);
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
