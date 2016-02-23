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
class ContainerScopeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        if (!method_exists('Symfony\Component\DependencyInjection\Container', 'enterScope')) {
            $this->markTestSkipped('Container scopes are not supported in this Symfony version.');
        }
    }

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
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = new ContainerScopeListener($container);
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
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));
        $container->enterScope(ContaoCoreBundle::SCOPE_BACKEND);

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = new ContainerScopeListener($container);
        $listener->onKernelFinishRequest(new FinishRequestEvent($kernel, $request, new Response()));

        $this->assertTrue($container->hasScope(ContaoCoreBundle::SCOPE_BACKEND));
        $this->assertFalse($container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND));
    }

    /**
     * Tests the onKernelController method without a request scope.
     */
    public function testWithoutRequestScope()
    {
        $container = new ContainerBuilder();
        $container->addScope(new Scope(ContaoCoreBundle::SCOPE_BACKEND));

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);

        $listener = new ContainerScopeListener($container);
        $listener->onKernelRequest(new GetResponseEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST));

        $this->assertTrue($container->hasScope(ContaoCoreBundle::SCOPE_BACKEND));
        $this->assertFalse($container->isScopeActive(ContaoCoreBundle::SCOPE_BACKEND));
    }

    /**
     * Tests the onKernelController method without the container scope.
     *
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function testWithoutContainerScope()
    {
        $container = new ContainerBuilder();

        /** @var HttpKernelInterface $kernel */
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\Kernel', ['test', false]);

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = new ContainerScopeListener($container);
        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST));
    }
}
