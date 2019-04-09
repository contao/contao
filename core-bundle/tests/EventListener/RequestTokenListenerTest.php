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

use Contao\Config;
use Contao\CoreBundle\EventListener\RequestTokenListener;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RequestTokenListenerTest extends TestCase
{
    public function testValidatesTheRequestToken(): void
    {
        $config = $this->mockConfiguredAdapter(['get' => false]);
        $framework = $this->mockContaoFramework([Config::class => $config]);
        $scopeMatcher = $this->createMock(ScopeMatcher::class);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->attributes->set('_token_check', true);

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new RequestTokenListener($framework, $scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener->onKernelRequest($event);
    }

    public function testValidatesTheRequestTokenUponContaoRequests(): void
    {
        $config = $this->mockConfiguredAdapter(['get' => false]);
        $framework = $this->mockContaoFramework([Config::class => $config]);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoRequest')
            ->willReturn(true)
        ;

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        $request = Request::create('/account.html');
        $request->setMethod('POST');

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new RequestTokenListener($framework, $scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener->onKernelRequest($event);
    }

    public function testFailsIfTheRequestTokenIsInvalid(): void
    {
        $config = $this->mockConfiguredAdapter(['get' => false]);
        $framework = $this->mockContaoFramework([Config::class => $config]);
        $scopeMatcher = $this->createMock(ScopeMatcher::class);

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->willReturn(false)
        ;

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->attributes->set('_token_check', true);

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new RequestTokenListener($framework, $scopeMatcher, $csrfTokenManager, 'contao_csrf_token');

        $this->expectException(InvalidRequestTokenException::class);

        $listener->onKernelRequest($event);
    }

    public function testDoesNotValidateTheRequestTokenUponNonPostRequests(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $request = Request::create('/account.html');
        $request->setMethod('GET');
        $request->attributes->set('_token_check', true);

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new RequestTokenListener($framework, $scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener->onKernelRequest($event);
    }

    public function testDoesNotValidateTheRequestTokenUponAjaxRequests(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->attributes->set('_token_check', true);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new RequestTokenListener($framework, $scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener->onKernelRequest($event);
    }

    public function testDoesNotValidateTheRequestTokenIfTheRequestAttributeIsFalse(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $request = Request::create('/account.html');
        $request->setMethod('POST');
        $request->attributes->set('_token_check', false);

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new RequestTokenListener($framework, $scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener->onKernelRequest($event);
    }

    public function testDoesNotValidateTheRequestTokenIfNoRequestAttributeAndNotAContaoRequest(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('getAdapter')
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoRequest')
            ->willReturn(false)
        ;

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $request = Request::create('/account.html');
        $request->setMethod('POST');

        $event = $this->createMock(GetResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $listener = new RequestTokenListener($framework, $scopeMatcher, $csrfTokenManager, 'contao_csrf_token');
        $listener->onKernelRequest($event);
    }
}
