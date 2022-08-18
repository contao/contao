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
        $request->server->set('SERVER_PORT', '80');
        $request->server->set('SERVER_NAME', 'example.com');
        $request->server->set('SCRIPT_NAME', '/path/to/contao/public/index.php');
        $request->server->set('SCRIPT_FILENAME', 'index.php');
        $request->server->set('REQUEST_URI', '/path/to/contao/public/contao/preview?page=1');

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
        $this->assertSame('http://example.com/path/to/contao/public/contao/preview', $event->getResponse()->headers->get('location'));
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
