<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\ToggleViewListener;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the ToggleViewListener class.
 *
 * @author Andreas Schempp <https:/github.com/aschempp>
 */
class ToggleViewListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ToggleViewListener', $listener);
    }

    /**
     * Tests that there is no response if the request scope is not set.
     */
    public function testWithoutRequestScope()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'desktop']);
        $request->attributes->set('_route', 'dummy');

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that there is no repsonse if the scope is not "frontend".
     */
    public function testNotInFrontend()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'desktop']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that there is no repsonse if there are no query parameters.
     */
    public function testNoView()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request();
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that there is a repsonse with a correct cookie for the desktop view.
     */
    public function testDesktopView()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'desktop']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    /**
     * Tests that there is a repsonse with a correct cookie for the mobile view.
     */
    public function testMobileView()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'mobile']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'mobile');
    }

    /**
     * Tests that there is a repsonse with a correct cookie for an invalid view.
     */
    public function testInvalidView()
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request(['toggle_view' => 'foobar']);
        $request->attributes->set('_route', 'dummy');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    /**
     * Tests the cookie path.
     */
    public function testCookiePath()
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

        $listener = new ToggleViewListener($this->framework, $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());

        $cookie = $this->getCookie($event->getResponse());

        $this->assertSame('/foo/bar', $cookie->getPath());
    }

    /**
     * Checks if a cookie exists and has the correct value.
     *
     * @param Response $response
     * @param string   $expectedValue
     */
    private function assertCookieValue(Response $response, $expectedValue)
    {
        $cookie = $this->getCookie($response);

        $this->assertNotNull($cookie);
        $this->assertSame($expectedValue, $cookie->getValue());
    }

    /**
     * Finds the TL_VIEW cookie in a response.
     *
     * @param Response $response
     *
     * @return Cookie|null
     */
    private function getCookie(Response $response)
    {
        /** @var Cookie[] $cookies */
        $cookies = $response->headers->getCookies();

        foreach ($cookies as $cookie) {
            if ('TL_VIEW' === $cookie->getName()) {
                return $cookie;
            }
        }

        return null;
    }
}
