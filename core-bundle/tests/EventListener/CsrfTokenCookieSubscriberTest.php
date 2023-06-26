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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Csrf\MemoryTokenStorage;
use Contao\CoreBundle\EventListener\CsrfTokenCookieSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('initialize')
            ->with([
                'foo' => 'bar',
                'generated' => $token,
            ])
        ;

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelRequest($this->getRequestEvent($request));
    }

    public function testDoesNotInitializeTheStorageUponSubrequests(): void
    {
        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent
            ->method('isMainRequest')
            ->willReturn(false)
        ;

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('initialize')
        ;

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelRequest($requestEvent);
    }

    public function testAddsTheTokenCookiesToTheResponse(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getUsedTokens')
            ->willReturn(['foo' => 'bar'])
        ;

        $response = new Response();

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
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
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag([
            'csrf_foo' => 'bar',
            'unrelated-cookie' => 'to-activate-csrf',
        ]);

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getUsedTokens')
            ->willReturn(['foo' => 'bar'])
        ;

        $response = new Response();

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testDoesNotAddTheTokenCookiesToTheResponseIfTheTokenCheckHasBeenDisabled(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);
        $request->attributes->set('_token_check', false);

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getUsedTokens')
        ;

        $response = new Response();

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testRemovesTheTokenCookiesAndReplacesTokenOccurrencesIfNoOtherCookiesArePresent(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['csrf_foo' => 'bar']);

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize(['tokenName' => 'tokenValue']);

        $tokenManager = new ContaoCsrfTokenManager(new RequestStack(), 'csrf_', null, $tokenStorage);
        $tokenValue1 = $tokenManager->getToken('tokenName');
        $tokenValue2 = $tokenManager->getToken('tokenName');

        $response = new Response(
            '<html><body><form>'
                .'<input name="REQUEST_TOKEN" value="'.$tokenValue1.'">'
                .'<input name="REQUEST_TOKEN" value="'.$tokenValue2.'">'
                .'</form></body></html>',
            200,
            ['Content-Type' => 'text/html', 'Content-Length' => 1234]
        );

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertSame(
            '<html><body><form>'
                .'<input name="REQUEST_TOKEN" value="">'
                .'<input name="REQUEST_TOKEN" value="">'
                .'</form></body></html>',
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
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['csrf_foo' => 'bar']);

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize([]);

        $response = new Response(
            '<html><body><form><input name="REQUEST_TOKEN" value="tokenValue"></form></body></html>',
            200,
            ['Content-Type' => 'text/html', 'Content-Length' => 1234]
        );

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertSame(
            '<html><body><form><input name="REQUEST_TOKEN" value="tokenValue"></form></body></html>',
            $response->getContent()
        );

        $this->assertTrue($response->headers->has('Content-Length'));
    }

    public function testDoesNotChangeTheResponseIfTokensAreNotFound(): void
    {
        $request = Request::create('https://foobar.com');
        $request->cookies = new InputBag(['csrf_foo' => 'bar']);

        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

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
            ->willReturn('<html><body><form><input name="REQUEST_TOKEN" value=""></form></body></html>')
        ;

        $response
            ->expects($this->never())
            ->method('setContent')
        ;

        $response->headers = new ResponseHeaderBag(['Content-Type' => 'text/html']);

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));
    }

    public function testDoesNotAddTheTokenCookiesToTheResponseUponSubrequests(): void
    {
        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $tokenStorage = $this->createMock(MemoryTokenStorage::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getUsedTokens')
        ;

        $responseEvent = new ResponseEvent(
            $this->createMock(Kernel::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            new Response()
        );

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    public function testDoesNotReplaceTheTokenOccurrencesIfNotAHtmlDocument(): void
    {
        $request = Request::create('https://foobar.com');

        $tokenStorage = new MemoryTokenStorage();
        $tokenStorage->initialize(['tokenName' => 'tokenValue']);

        $tokenManager = new ContaoCsrfTokenManager(new RequestStack(), 'csrf_', null, $tokenStorage);
        $tokenValue = $tokenManager->getToken('tokenName');

        $response = new Response(
            'value="'.$tokenValue.'"',
            200,
            ['Content-Type' => 'application/octet-stream']
        );

        $listener = new CsrfTokenCookieSubscriber($tokenManager, $tokenStorage);
        $listener->onKernelResponse($this->getResponseEvent($request, $response));

        $this->assertSame('value="'.$tokenValue.'"', $response->getContent());
    }

    public function getRequestEvent(Request|null $request = null): RequestEvent
    {
        if (!$request) {
            $request = new Request();
        }

        return new RequestEvent($this->createMock(Kernel::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function getResponseEvent(Request|null $request = null, Response|null $response = null): ResponseEvent
    {
        if (!$request) {
            $request = new Request();
        }

        if (!$response) {
            $response = new Response();
        }

        return new ResponseEvent($this->createMock(Kernel::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
