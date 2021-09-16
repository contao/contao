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

use Contao\CoreBundle\EventListener\BackendPreviewRedirectListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BackendPreviewRedirectListenerTest extends TestCase
{
    public function testRedirectsToTheMainEntryPoint(): void
    {
        $request = new Request();
        $request->attributes->set('_preview', true);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false)
        ;

        $listener = new BackendPreviewRedirectListener($scopeMatcher);
        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testDoesNotRedirectUponSubrequests(): void
    {
        $request = new Request();
        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->never())
            ->method('isFrontendRequest')
        ;

        $listener = new BackendPreviewRedirectListener($scopeMatcher);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotRedirectIfNoPreview(): void
    {
        $request = new Request();
        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->never())
            ->method('isFrontendRequest')
        ;

        $listener = new BackendPreviewRedirectListener($scopeMatcher);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotRedirectIfPreviewIsFalse(): void
    {
        $request = new Request();
        $request->attributes->set('_preview', false);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->never())
            ->method('isFrontendRequest')
        ;

        $listener = new BackendPreviewRedirectListener($scopeMatcher);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotRedirectIfPreviewIsAllowed(): void
    {
        $request = new Request();
        $request->attributes->set('_preview', true);
        $request->attributes->set('_allow_preview', true);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false)
        ;

        $listener = new BackendPreviewRedirectListener($scopeMatcher);
        $listener($event);

        $this->assertFalse($event->hasResponse());
    }
}
