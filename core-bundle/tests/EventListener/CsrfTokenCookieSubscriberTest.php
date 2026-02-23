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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

class CsrfTokenCookieSubscriberTest extends TestCase
{
    public function testInitializesTheStorage(): void
    {
        $token = (new UriSafeTokenGenerator())->generateToken();
        $request = Request::create('https://foobar.com');

        $request->cookies = new InputBag([
            'csrf_foo' => 'bar',
            'csrf_generated' => $token,
            'not_csrf' => 'baz',
            'csrf_bar' => '"<>!&', // ignore invalid characters
        ]);

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
        $requestEvent = $this->createStub(RequestEvent::class);
        $requestEvent
            ->method('isMainRequest')
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
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

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

    public function testDoesNotAddTheTokenCookiesToTheResponseIfAllCookiesAreDeleted(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getUsedTokens')
        ;

        $response = new Response();
        $response->headers->clearCookie('unrelated-cookie');

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $cookies = $response->headers->getCookies();

        $this->assertCount(1, $cookies);
        $this->assertSame('unrelated-cookie', $cookies[0]->getName());
        $this->assertTrue($cookies[0]->isCleared());
    }

    public function testDoesNotAddTheTokenCookiesToTheResponseIfTheyAlreadyExist(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag([
            'csrf_foo' => 'bar',
            'unrelated-cookie' => 'to-activate-csrf',
        ]);

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

    public function testDoesNotAddTheTokenCookiesIfTheSessionWasStartedButClearedAgain(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('foobar', 'foobaz'); // This starts the session
        $session->remove('foobar'); // This removes the value but the session remains started

        $request = Request::create('https://foobar.com');
        $request->setSession($session);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getUsedTokens')
        ;

        $response = new Response();

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testDoesNotAddTheTokenCookiesToTheResponseIfTheTokenCheckHasBeenDisabled(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);
        $request->attributes->set('_token_check', false);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getUsedTokens')
        ;

        $response = new Response();

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testRemovesTheTokenCookiesIfNoOtherCookiesArePresent(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['csrf_foo' => 'bar']);

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize(['tokenName' => 'tokenValue']);

        $response = new Response('',
            200,
            ['Content-Type' => 'text/html', 'Content-Length' => 1234],
        );

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

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
        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getUsedTokens')
        ;

        $responseEvent = new ResponseEvent(
            $this->createStub(Kernel::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $listener = new CsrfTokenCookieSubscriber($tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    public function getRequestEvent(Request|null $request = null): RequestEvent
    {
        if (!$request) {
            $request = new Request();
        }

        return new RequestEvent($this->createStub(Kernel::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function getResponseEvent(Request|null $request = null, Response|null $response = null): ResponseEvent
    {
        if (!$request) {
            $request = new Request();
        }

        if (!$response) {
            $response = new Response();
        }

        return new ResponseEvent($this->createStub(Kernel::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
