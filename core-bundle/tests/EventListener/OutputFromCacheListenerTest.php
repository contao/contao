<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\OutputFromCacheListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the OutputFromCacheListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class OutputFromCacheListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new OutputFromCacheListener();

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\OutputFromCacheListener', $listener);
    }

    /**
     * Tests adding a response to the event.
     */
    public function testFrontendScope()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container = new Container();
        $request   = new Request();
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new OutputFromCacheListener();

        $container->addScope(new Scope('frontend'));
        $container->enterScope('frontend');

        $request->attributes->set('_route', 'dummy');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
    }

    /**
     * Tests that there is no repsonse if the scope is not "frontend".
     */
    public function testInvalidScope()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $container = new Container();
        $request   = new Request();
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new OutputFromCacheListener();

        $container->addScope(new Scope('backend'));
        $container->enterScope('backend');

        $request->attributes->set('_route', 'dummy');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * Tests that there is no repsonse if there is no container.
     */
    public function testWithoutContainer()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $request   = new Request();
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new OutputFromCacheListener();

        $request->attributes->set('_route', 'dummy');

        $listener->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }
}
