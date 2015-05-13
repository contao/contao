<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\SessionListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests the SessionListenerTest class.
 *
 * @author Yanick Witschi <https:/github.com/toflar>
 */
class SessionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new SessionListener($this->getSession());

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\SessionListener', $listener);
    }

    /**
     * Tests that the attribute bags are registered.
     */
    public function testAttributeBagsRegistered()
    {
        $session = $this->getSession();

        $listener = new SessionListener($session);
        $listener->onKernelRequest();

        $this->assertInstanceOf(
            'Contao\\CoreBundle\\Session\\Attribute\\ArrayAttributeBag',
            $session->getBag('contao_backend')
        );
        $this->assertInstanceOf(
            'Contao\\CoreBundle\\Session\\Attribute\\ArrayAttributeBag',
            $session->getBag('contao_frontend')
        );
    }

    /**
     * Returns a session for the tests.
     *
     * @return SessionInterface The session object
     */
    private function getSession()
    {
        return new Session(new MockArraySessionStorage());
    }
}
