<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener\HeaderReplay;

use Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;
use Terminal42\HeaderReplay\EventListener\HeaderReplayListener;

/**
 * Tests the UserSessionListener class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class UserSessionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new UserSessionListener($this->mockScopeMatcher(), false);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener', $listener);
    }

    /**
     * Tests adding the T42-Force-No-Cache header.
     *
     * @param string $cookie
     * @param string $hash
     *
     * @dataProvider getForceNoCacheHeaderData
     */
    public function testAddsTheForceNoCacheHeader($cookie, $hash)
    {
        $session = new Session();
        $session->setId('foobar-id');

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->cookies->set($cookie, $hash);
        $request->setSession($session);

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );

        $this->assertNotNull($request->getSession());
        $this->assertTrue($request->cookies->has($cookie));
    }

    /**
     * Provides the data for the testAddsTheForceNoCacheHeader() method.
     *
     * @return array
     */
    public function getForceNoCacheHeaderData()
    {
        return [
            'Front end user' => ['FE_USER_AUTH', '4549584220117984a1be92c5f9eb980e5d2771d6'],
            'Back end user' => ['BE_USER_AUTH', 'f6d5c422c903288859fb5ccf03c8af8b0fb4b70a'],
        ];
    }

    /**
     * Tests that no header is added outside the Contao scope.
     */
    public function testDoesNotAddTheForceNoCacheHeaderIfNotInContaoScope()
    {
        $event = new HeaderReplayEvent(new Request(), new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );
    }

    /**
     * Tests that no header is added when the request has no session.
     */
    public function testDoesNotAddTheForceNoCacheIfThereIsNoSession()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );
    }

    /**
     * Tests that no header is added when the request has no user authentication cookie.
     */
    public function testDoesNotAddTheForceNoCacheIfThereIsNoCookie()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->setSession(new Session());

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );

        $this->assertNotNull($request->getSession());
    }

    /**
     * Tests that no header is added if the authentication cookie has an invalid value.
     */
    public function testDoesNotAddTheForceNoCacheIfTheCookieIsInvalid()
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->cookies->set('BE_USER_AUTH', 'foobar');
        $request->setSession(new Session());

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey(
            strtolower(HeaderReplayListener::FORCE_NO_CACHE_HEADER_NAME),
            $event->getHeaders()->all()
        );

        $this->assertNotNull($request->getSession());
        $this->assertTrue($request->cookies->has('BE_USER_AUTH'));
    }
}
