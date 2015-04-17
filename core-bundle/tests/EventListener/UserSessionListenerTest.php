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
     * Test session bag is never requested when having no user on kernel.request.
     */
    public function testListenerSkipIfNoUserOnKernelRequest()
    {
        $request = new Request();
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->never())->method('getBag');

        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->once())->method('getToken')->willReturn(null);

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Test session bag is never requested when having no master request on
     * kernel.request.
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
     * having no user on kernel.response.
     */
    public function testListenerSkipIfNoUserOnKernelResponse()
    {
        $request = new Request();
        $response = new Response();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->never())->method('getBag');

        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->once())->method('getToken')->willReturn(null);

        $connection = $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false);
        $connection->expects($this->never())->method('prepare');
        $connection->expects($this->never())->method('excecute');

        $listener = $this->getListener($session, $connection, $tokenStorage);

        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Test neither session bag nor doctrine is requested when
     * having no master request on kernel.response.
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
        $connection = $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false);
        $connection->expects($this->never())->method('prepare');
        $connection->expects($this->never())->method('excecute');

        $listener = $this->getListener($session, $connection);

        $listener->onKernelResponse($responseEvent);
    }


    private function getListener(
        $session = null,
        $connection = null,
        $tokenStorage = null)
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

        if (null === $tokenStorage) {
            $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        }

        return new UserSessionListener($session, $connection, $tokenStorage);
    }
}
