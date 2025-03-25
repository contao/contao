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
use Contao\CoreBundle\EventListener\RequestTokenListener;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class RequestTokenListenerTest extends TestCase
{
    public function testValidatesTheRequestToken(): void
    {
        $request = $this->createPostRequest();

        $request->attributes->set('_token_check', true);
        $request->request->set('REQUEST_TOKEN', 'foo');

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $this->validateRequestTokenForRequest($request);
    }

    public function testValidatesTheRequestTokenUponAuthenticatedRequest(): void
    {
        $request = $this->createPostRequest();

        $request->headers->set('PHP_AUTH_USER', 'user');
        $request->request->set('REQUEST_TOKEN', 'foo');

        $this->validateRequestTokenForRequest($request);
    }

    public function testValidatesTheRequestTokenUponRunningSession(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('foobar', 'foobaz');

        $request = $this->createPostRequest();
        $request->setSession($session);

        $request->attributes->set('_token_check', true);
        $request->request->set('REQUEST_TOKEN', 'foo');

        $this->validateRequestTokenForRequest($request);
    }

    public function testDoesNotValidateTheRequestTokenWithoutCookies(): void
    {
        $request = $this->createPostRequest();

        $this->validateRequestTokenForRequest($request, false);
    }

    public function testDoesNotValidateTheRequestTokenWithCsrfCookiesOnly(): void
    {
        $request = $this->createPostRequest();
        $request->cookies = new InputBag(['csrf_contao_csrf_token' => 'value']);

        $this->validateRequestTokenForRequest($request, false);
    }

    #[DataProvider('getAttributeAndRequest')]
    public function testValidatesTheRequestTokenDependingOnTheRequest(bool $setAttribute, bool|null $tokenCheck, bool $isContaoRequest, bool $isValidToken): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isContaoRequest')
            ->willReturn($isContaoRequest)
        ;

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->expects($isValidToken ? $this->once() : $this->never())
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $request = $this->createPostRequest();
        $request->request->set('REQUEST_TOKEN', 'foo');

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        if ($setAttribute) {
            $request->attributes->set('_token_check', $tokenCheck);
        }

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    public static function getAttributeAndRequest(): iterable
    {
        yield 'no attribute, Contao request' => [false, false, true, true];
        yield 'no attribute, not a Contao request' => [false, false, false, false];
        yield 'attribute, Contao request' => [true, true, true, true];
        yield 'attribute, not a Contao request' => [true, true, false, true];
        yield 'falsey attribute, not a Contao request' => [true, null, false, false];
    }

    public function testFailsIfTheRequestTokenIsInvalid(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isContaoRequest')
            ->willReturn(true)
        ;

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $request = $this->createPostRequest();

        $request->request->set('REQUEST_TOKEN', 'foo');

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');

        $this->expectException(InvalidRequestTokenException::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $listener($event);
    }

    public function testDoesNotValidateTheRequestTokenUponNonPostRequests(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $request = Request::create('/account.html');
        $request->setMethod('GET');

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    public function testDoesNotValidateTheRequestTokenUponAjaxRequests(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $request = $this->createPostRequest();

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    public function testDoesNotValidateTheRequestTokenUponPreflightedRequests(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $request = $this->createPostRequest('text/xml'); // Content-Type requiring preflighted request

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    public function testDoesNotValidateTheRequestTokenIfTheRequestAttributeIsFalse(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $request = $this->createPostRequest();

        $request->attributes->set('_token_check', false);

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    public function testDoesNotValidateTheRequestTokenIfNoRequestAttributeAndNotAContaoRequest(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoRequest')
            ->willReturn(false)
        ;

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $request = $this->createPostRequest();

        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    public function testDoesNotValidateTheRequestTokenIfNotAMainRequest(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->never())
            ->method('getRequest')
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(false)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    #[DataProvider('simpleCorsRequestContentTypeProvider')]
    public function testSimpleCorsRequest(string $contentType): void
    {
        $request = $this->createPostRequest($contentType);

        $this->assertTrue(RequestTokenListener::isSimpleCorsRequest($request));
    }

    public static function simpleCorsRequestContentTypeProvider(): array
    {
        return [
            'urlencoded basic' => ['application/x-www-form-urlencoded'],
            'urlencoded with UTF-8' => ['application/x-www-form-urlencoded; charset=UTF-8'],
            'urlencoded with ISO' => ['application/x-www-form-urlencoded; charset=ISO-8859-1'],
            'multipart basic' => ['multipart/form-data'],
            'multipart with WebKit boundary' => ['multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW'],
            'multipart with charset and custom boundary' => ['multipart/form-data; charset=UTF-8; boundary=----MyCustomBoundary12345'],
            'text plain basic' => ['text/plain'],
            'text plain with UTF-8' => ['text/plain; charset=UTF-8'],
            'text plain with ISO' => ['text/plain; charset=ISO-8859-1'],
            'text plain flowed' => ['text/plain; format=flowed'],
            'text plain UTF-8 flowed' => ['text/plain; charset=UTF-8; format=flowed'],
            'text plain uppercase' => ['TEXT/PLAIN'],
        ];
    }

    private function validateRequestTokenForRequest(Request $request, bool $shouldValidate = true): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isContaoRequest')
            ->willReturn(true)
        ;

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->expects($shouldValidate ? $this->once() : $this->never())
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $csrfTokenManager
            ->method('canSkipTokenValidation')
            ->willReturnCallback(
                function () {
                    $tokenManager = new ContaoCsrfTokenManager($this->createMock(RequestStack::class), 'csrf_', new UriSafeTokenGenerator(), $this->createMock(TokenStorageInterface::class));

                    return $tokenManager->canSkipTokenValidation(...\func_get_args());
                },
            )
        ;

        $event = $this->createMock(RequestEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->once())
            ->method('isMainRequest')
            ->willReturn(true)
        ;

        $listener = new RequestTokenListener($scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener($event);
    }

    private function createPostRequest(string $contentType = 'application/x-www-form-urlencoded'): Request
    {
        return Request::create('/account.html', 'POST', [], [], [], ['CONTENT_TYPE' => $contentType]);
    }
}
