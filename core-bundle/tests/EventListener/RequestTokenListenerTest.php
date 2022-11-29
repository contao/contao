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
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

class RequestTokenListenerTest extends TestCase
{
    public function testValidatesTheRequestToken(): void
    {
        $request = Request::create('/account.html', 'POST');
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foo');
        $request->attributes->set('_token_check', true);
        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);

        $this->validateRequestTokenForRequest($request);
    }

    public function testValidatesTheRequestTokenUponAuthenticatedRequest(): void
    {
        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foo');
        $request->attributes->set('_token_check', true);
        $request->headers->set('PHP_AUTH_USER', 'user');

        $this->validateRequestTokenForRequest($request);
    }

    public function testValidatesTheRequestTokenUponRunningSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->method('isStarted')
            ->willReturn(true)
        ;

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foo');
        $request->attributes->set('_token_check', true);
        $request->setSession($session);

        $this->validateRequestTokenForRequest($request);
    }

    public function testDoesNotValidateTheRequestTokenWithoutCookies(): void
    {
        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->attributes->set('_token_check', true);

        $this->validateRequestTokenForRequest($request, false);
    }

    public function testDoesNotValidateTheRequestTokenWithCsrfCookiesOnly(): void
    {
        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->attributes->set('_token_check', true);
        $request->cookies = new InputBag(['csrf_contao_csrf_token' => 'value']);

        $this->validateRequestTokenForRequest($request, false);
    }

    public function testValidatesTheRequestTokenUponContaoRequests(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoRequest')
            ->willReturn(true)
        ;

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $request = Request::create('/account.html');
        $request->setMethod('POST');
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
        $listener($event);
    }

    public function testFailsIfTheRequestTokenIsInvalid(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->request->set('REQUEST_TOKEN', 'foo');
        $request->attributes->set('_token_check', true);
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
        $request->attributes->set('_token_check', true);
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

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->attributes->set('_token_check', true);
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

    public function testDoesNotValidateTheRequestTokenIfTheRequestAttributeIsFalse(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->cookies = new InputBag(['unrelated-cookie' => 'to-activate-csrf']);
        $request->attributes->set('_token_check', false);

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

        $request = Request::create('/account.html');
        $request->setMethod('POST');
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

    private function validateRequestTokenForRequest(Request $request, bool $shouldValidate = true): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);

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
                }
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
}
