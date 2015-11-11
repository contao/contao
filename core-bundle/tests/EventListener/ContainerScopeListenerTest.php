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
        $listener = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request = new Request();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertTrue($container->hasScope(ContaoCoreBundle::SCOPE_BACKEND));
        $this->assertTrue($container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND));
    }

    /**
     * Tests the onKernelFinishRequest method.
     */
    public function testOnKernelFinishRequest()
    {
        $container = new ContainerBuilder();
        $listener = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request = new Request();
        $response = new Response();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener->onKernelFinishRequest(new FinishRequestEvent($kernel, $request, $response));

        $this->assertTrue($container->hasScope(ContaoCoreBundle::SCOPE_BACKEND));
        $this->assertFalse($container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND));
    }

    /**
     * Tests the onKernelController method without a request scope.
     */
    public function testWithoutRequestScope()
    {
        $container = new ContainerBuilder();
        $listener = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request = new Request();

        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertTrue($container->hasScope(ContaoCoreBundle::SCOPE_BACKEND));
        $this->assertFalse($container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND));
    }

    /**
     * Tests the onKernelController method without the container scope.
     */
    public function testWithoutContainerScope()
    {
        $container = new ContainerBuilder();
        $listener = new ContainerScopeListener($container);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);
        $request = new Request();

        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));

        $this->assertFalse($container->hasScope(ContaoCoreBundle::SCOPE_BACKEND));
        $this->assertFalse($container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND));
    }
}
