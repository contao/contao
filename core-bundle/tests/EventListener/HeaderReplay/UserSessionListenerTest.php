<?php

declare(strict_types=1);

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
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;

class UserSessionListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new UserSessionListener($this->mockScopeMatcher(), false);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener', $listener);
    }

    /**
     * @param string $cookie
     * @param string $hash
     *
     * @dataProvider getForceNoCacheHeaderData
     */
    public function testAddsTheForceNoCacheHeader(string $cookie, string $hash): void
    {
        $session = $this->mockSession();
        $session->setId('foobar-id');

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->cookies->set($cookie, $hash);
        $request->setSession($session);

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayHasKey('t42-force-no-cache', $event->getHeaders()->all());
        $this->assertNotNull($request->getSession());
        $this->assertTrue($request->cookies->has($cookie));
    }

    /**
     * @return array
     */
    public function getForceNoCacheHeaderData(): array
    {
        return [
            'Front end user' => ['FE_USER_AUTH', '4549584220117984a1be92c5f9eb980e5d2771d6'],
            'Back end user' => ['BE_USER_AUTH', 'f6d5c422c903288859fb5ccf03c8af8b0fb4b70a'],
        ];
    }

    public function testDoesNotAddTheForceNoCacheHeaderIfNotInContaoScope(): void
    {
        $event = new HeaderReplayEvent(new Request(), new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
    }

    public function testDoesNotAddTheForceNoCacheIfThereIsNoSession(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
    }

    public function testDoesNotAddTheForceNoCacheIfThereIsNoCookie(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->setSession($this->mockSession());

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
        $this->assertNotNull($request->getSession());
    }

    public function testDoesNotAddTheForceNoCacheIfTheCookieIsInvalid(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->cookies->set('BE_USER_AUTH', 'foobar');
        $request->setSession($this->mockSession());

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), false);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
        $this->assertNotNull($request->getSession());
        $this->assertTrue($request->cookies->has('BE_USER_AUTH'));
    }
}
