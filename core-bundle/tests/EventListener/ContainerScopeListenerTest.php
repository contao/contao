<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\ContainerScopeListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the ContainerScopeListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ContainerScopeListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new ContainerScopeListener(new ContainerBuilder());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ContainerScopeListener', $listener);
    }

    /**
     * Tests the onKernelRequest method.
     */
    public function testOnKernelRequest()
    {
        $container = new ContainerBuilder();
        $listener  = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request();

        $container->addScope(new Scope('backend'));
        $request->attributes->set('_scope', 'backend');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertTrue($container->hasScope('backend'));
        $this->assertTrue($container->isScopeActive('backend'));
    }

    /**
     * Tests the onKernelFinishRequest method.
     */
    public function testOnKernelFinishRequest()
    {
        $container = new ContainerBuilder();
        $listener  = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request();
        $response = new Response();

        $container->addScope(new Scope('backend'));
        $container->enterScope('backend');
        $request->attributes->set('_scope', 'backend');

        $listener->onKernelFinishRequest(new FinishRequestEvent($kernel, $request, $response));

        $this->assertTrue($container->hasScope('backend'));
        $this->assertFalse($container->isScopeActive('backend'));
    }

    /**
     * Tests the onKernelController method without a request scope.
     */
    public function testWithoutRequestScope()
    {
        $container = new ContainerBuilder();
        $listener  = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request();

        $container->addScope(new Scope('backend'));

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertTrue($container->hasScope('backend'));
        $this->assertFalse($container->isScopeActive('backend'));
    }

    /**
     * Tests the onKernelController method without the container scope.
     */
    public function testWithoutContainerScope()
    {
        $container = new ContainerBuilder();
        $listener  = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel   = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request  = new Request();

        $request->attributes->set('_scope', 'backend');

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertFalse($container->hasScope('backend'));
        $this->assertFalse($container->isScopeActive('backend'));
    }
}
