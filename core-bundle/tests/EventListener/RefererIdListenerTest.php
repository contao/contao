<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\RefererIdListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the RefererIdListener class.
 *
 * @author Yanick Witschi <https:/github.com/toflar>
 */
class RefererIdListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new RefererIdListener($this->mockTokenManager());

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\RefererIdListener', $listener);
    }

    /**
     * Tests that the token is added to the request.
     */
    public function testTokenAddedToRequest()
    {
        /** @var HttpKernelInterface $kernel */
        $kernel    = $this->getMockForAbstractClass('Symfony\\Component\\HttpKernel\\Kernel', ['test', false]);
        $request   = new Request();
        $event     = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $listener  = new RefererIdListener($this->mockTokenManager());
        $container = new Container();

        $container->addScope(new Scope('backend'));
        $container->enterScope('backend');

        $listener->setContainer($container);
        $listener->onKernelRequest($event);

        $this->assertSame('testValue', $request->attributes->get('_contao_referer_id'));
    }
}
