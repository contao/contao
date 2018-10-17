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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\ToggleViewListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ToggleViewListenerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->mockContainer();
        $container->set('session', $this->mockSession());
        $container->set('request_stack', new RequestStack());

        System::setContainer($container);

        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    public function testRedirectsToDesktopView(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'desktop']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->mockContaoFramework(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    public function testRedirectsToMobileView(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'mobile']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->mockContaoFramework(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'mobile');
    }

    public function testDoesNotSetAResponseIfThereIsNoRequestScope(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'desktop']);
        $request->attributes->set('_route', 'dummy');

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->mockContaoFramework(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotSetAResponseIfNotInFrontEndScope(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'desktop']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->mockContaoFramework(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testDoesNotSetAResponseIfThereAreNoQueryParameters(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->mockContaoFramework(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testFallsBackToDesktopIfTheRequestedViewDoesNotExist(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'foobar']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->mockContaoFramework(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    public function testSetsTheCorrectCookiePath(): void
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'desktop']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        // Set the base path to /foo/bar
        $reflection = new \ReflectionClass($request);
        $basePath = $reflection->getProperty('basePath');
        $basePath->setAccessible(true);
        $basePath->setValue($request, '/foo/bar');

        $listener = new ToggleViewListener($this->mockContaoFramework(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());

        $cookie = $this->getCookie($event->getResponse());

        $this->assertNotNull($cookie);
        $this->assertSame('/foo/bar', $cookie->getPath());
    }

    private function assertCookieValue(Response $response, string $expectedValue): void
    {
        $cookie = $this->getCookie($response);

        $this->assertNotNull($cookie);
        $this->assertSame($expectedValue, $cookie->getValue());
    }

    /**
     * Finds the TL_VIEW cookie in a response.
     */
    private function getCookie(Response $response): ?Cookie
    {
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ('TL_VIEW' === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }
}
