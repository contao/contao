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
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

    /**
     * Test session bag is never requested when having no master request.
     */
    public function testListenerSkipIfNoMasterRequestOnKernelRequest()
    {
        $request = new Request();
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->never())->method('getBag');

        $listener = $this->getListener($session);
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Test neither session bag nor doctrine is requested when
     * having no master request.
     */
    public function testListenerSkipIfNoMasterRequestOnKernelResponse()
    {
        $request = new Request();
        $response = new Response();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );
        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session\\SessionInterface');
        $session->expects($this->never())->method('getBag');
        $connection = $this->getMock('Doctrine\\DBAL\\Connection');
        $connection->expects($this->never())->method('prepare');
        $connection->expects($this->never())->method('excecute');

        $listener = $this->getListener($session, $connection);

        $listener->onKernelResponse($responseEvent);
    }


    private function getListener($session = null, $connection = null)
    {
        if (null === $session) {
            $session = $this->mockSession();
        }

        if (null === $connection) {
            $connection = $this->getMock(
                'Doctrine\\DBAL\\Connection',
                [],
                [],
                '',
                false
            );
        }

        return new UserSessionListener($session, $connection);
    }
}
