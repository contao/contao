<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\ToggleViewListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the ToggleViewListener class.
 *
 * @author Andreas Schempp <https:/github.com/aschempp>
 */
class ToggleViewListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new ToggleViewListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ToggleViewListener', $listener);
    }

    /**
     * Tests no response without query parameter.
     */
    public function testNoView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request();
        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new ToggleViewListener();

        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests response with correct cookie on toggle_view=desktop query parameter.
     */
    public function testDesktopView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request(['toggle_view' => 'desktop']);
        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new ToggleViewListener();

        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    /**
     * Tests response with correct cookie on toggle_view=mobile query parameter.
     */
    public function testMobileView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request(['toggle_view' => 'mobile']);
        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new ToggleViewListener();

        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'mobile');
    }

    /**
     * Tests response with correct cookie on toggle_view=foobar query parameter.
     */
    public function testInvalidView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request(['toggle_view' => 'foobar']);
        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new ToggleViewListener();

        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    /**
     * Tests the cookie path matches the request path
     */
    public function testCookiePath()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = $this->getMock(
            'Symfony\Component\HttpFoundation\Request',
            ['getBasePath'],
            [['toggle_view' => 'desktop']]
        );

        $request->expects($this->atLeastOnce())->method('getBasePath')->will($this->returnValue('/foo/bar'));

        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new ToggleViewListener();

        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());

        $cookie = $this->getCookie($event->getResponse());

        $this->assertEquals('/foo/bar', $cookie->getPath());
    }

    /**
     * Check if response cookie exists and has the correct values.
     *
     * @param Response $response
     * @param string   $expectedValue
     */
    private function assertCookieValue(Response $response, $expectedValue)
    {
        $cookie = $this->getCookie($response);

        $this->assertNotNull($cookie);
        $this->assertEquals($expectedValue, $cookie->getValue());
    }

    /**
     * Find the TL_VIEW cookie in a Respone
     *
     * @param Response $response
     *
     * @return null|Cookie
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
