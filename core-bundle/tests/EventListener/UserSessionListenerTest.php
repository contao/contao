<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\UserSessionListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Tests the UserSessionListener class.
 *
 * @author Yanick Witschi <https:/github.com/toflar>
 */
class UserSessionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = $this->getListener();

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\UserSessionListener', $listener);
    }

    public function testListenerSkipIfNoMasterRequestOnKernelRequest()
    {
        $request = new Request();
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            Kernel::SUB_REQUEST
        );
        $listener = $this->getListener();

        $this->assertNull($listener->onKernelRequest($responseEvent));
    }

    public function testListenerSkipIfNoMasterRequestOnKernelResponse()
    {
        $request = new Request();
        $response = new Response();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            Kernel::SUB_REQUEST,
            $response
        );
        $listener = $this->getListener();

        $this->assertNull($listener->onKernelResponse($responseEvent));
    }


    private function getListener()
    {
        return new UserSessionListener(
            $this->mockSession(),
            $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false)
        );
    }
}
