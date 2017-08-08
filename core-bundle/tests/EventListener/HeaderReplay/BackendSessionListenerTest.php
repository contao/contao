<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener\HeaderReplay;

use Contao\CoreBundle\EventListener\HeaderReplay\BackendSessionListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;
use Terminal42\HeaderReplay\EventListener\HeaderReplayListener;

/**
 * Tests the BackendSessionListener class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class BackendSessionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new BackendSessionListener($this->mockScopeMatcher(), false);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\HeaderReplay\BackendSessionListener', $listener);
    }

    /**
     * Tests that no header is added outside the Contao back end scope.
     */
    public function testOnReplayWithNoBackendScope()
    {
        $event = new HeaderReplayEvent(new Request(), new ResponseHeaderBag());

        $listener = new BackendSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );
    }

    /**
     * Tests that no header is added when the request has no session.
     */
    public function testOnReplayWithNoSession()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new BackendSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );
    }

    /**
     * Tests that no header is added when the request has no back end user authentication cookie.
     */
    public function testOnReplayWithNoAuthCookie()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->setSession(new Session());

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new BackendSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );

        $this->assertNotNull($request->getSession());
    }

    /**
     * Tests that no header is added if the auth cookie has an invalid value.
     */
    public function testOnReplayWithNoValidCookie()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->cookies->set('BE_USER_AUTH', 'foobar');
        $request->setSession(new Session());

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new BackendSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );

        $this->assertNotNull($request->getSession());
        $this->assertTrue($request->cookies->has('BE_USER_AUTH'));
    }

    /**
     * Tests that the header is correctly added when scope and auth cookie are correct.
     */
    public function testOnReplay()
    {
        $session = new Session();
        $session->setId('foobar-id');

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->cookies->set('BE_USER_AUTH', 'f6d5c422c903288859fb5ccf03c8af8b0fb4b70a');
        $request->setSession($session);

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new BackendSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );

        $this->assertNotNull($request->getSession());
        $this->assertTrue($request->cookies->has('BE_USER_AUTH'));
    }
}
