<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\ToggleViewListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
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

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\ToggleViewListener', $listener);
    }

    /**
     * Tests that there is no response if there is no container.
     */
    public function testWithoutContainer()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $request  = new Request(['toggle_view' => 'desktop']);
        $event    = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener = new ToggleViewListener();

        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that there is no repsonse if the scope is not "frontend".
     */
    public function testNotInFrontend()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container = new Container();
        $request   = new Request(['toggle_view' => 'desktop']);
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new ToggleViewListener();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);

        $request->attributes->set('_route', 'dummy');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that there is no repsonse if there are no query parameters.
     */
    public function testNoView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container = new Container();
        $request   = new Request();
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new ToggleViewListener();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $request->attributes->set('_route', 'dummy');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that there is a repsonse with a correct cookie for the desktop view.
     */
    public function testDesktopView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container = new Container();
        $request   = new Request(['toggle_view' => 'desktop']);
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new ToggleViewListener();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $request->attributes->set('_route', 'dummy');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    /**
     * Tests that there is a repsonse with a correct cookie for the mobile view.
     */
    public function testMobileView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container = new Container();
        $request   = new Request(['toggle_view' => 'mobile']);
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new ToggleViewListener();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $request->attributes->set('_route', 'dummy');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'mobile');
    }

    /**
     * Tests that there is a repsonse with a correct cookie for an invalid view.
     */
    public function testInvalidView()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container = new Container();
        $request   = new Request(['toggle_view' => 'foobar']);
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new ToggleViewListener();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $request->attributes->set('_route', 'dummy');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertCookieValue($event->getResponse(), 'desktop');
    }

    /**
     * Tests the cookie path.
     */
    public function testCookiePath()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel     = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container  = new Container();
        $request    = new Request(['toggle_view' => 'desktop']);
        $event      = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener   = new ToggleViewListener();
        $reflection = new \ReflectionClass($request);

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_FRONTEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_FRONTEND);

        $request->attributes->set('_route', 'dummy');

        // Set the base path to /foo/bar
        $basePath = $reflection->getProperty('basePath');
        $basePath->setAccessible(true);
        $basePath->setValue($request, '/foo/bar');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());

        $cookie = $this->getCookie($event->getResponse());

        $this->assertEquals('/foo/bar', $cookie->getPath());
    }

    /**
     * Checks if a cookie exists and has the correct value.
     *
     * @param Response $response      The response object
     * @param string   $expectedValue The expected value
     */
    private function assertCookieValue(Response $response, $expectedValue)
    {
        $cookie = $this->getCookie($response);

        $this->assertNotNull($cookie);
        $this->assertEquals($expectedValue, $cookie->getValue());
    }

    /**
     * Finds the TL_VIEW cookie in a response.
     *
     * @param Response $response The response object
     *
     * @return Cookie|null The cookie object or null
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
