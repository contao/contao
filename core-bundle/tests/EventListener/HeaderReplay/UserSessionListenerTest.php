<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener\HeaderReplay;

use Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;

class UserSessionListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $scopeMatcher = $this->mockScopeMatcher();
        $tokenChecker = $this->createMock(TokenChecker::class);
        $listener = new UserSessionListener($scopeMatcher, $tokenChecker);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener', $listener);
    }

    /**
     * @dataProvider getForceNoCacheHeaderData
     *
     * @param string $method
     */
    public function testAddsTheForceNoCacheHeader(string $method): void
    {
        $session = $this->mockSession();
        $session->setId('foobar-id');

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->setSession($session);

        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->expects($this->atLeastOnce())
            ->method($method)
            ->willReturn(true)
        ;

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), $tokenChecker);
        $listener->onReplay($event);

        $this->assertArrayHasKey('t42-force-no-cache', $event->getHeaders()->all());
        $this->assertNotNull($request->getSession());
    }

    /**
     * @return array
     */
    public function getForceNoCacheHeaderData(): array
    {
        return [['hasFrontendUser'], ['hasBackendUser']];
    }

    public function testDoesNotAddTheForceNoCacheHeaderIfNotInContaoScope(): void
    {
        $event = new HeaderReplayEvent(new Request(), new ResponseHeaderBag());
        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->expects($this->never())
            ->method('hasBackendUser')
        ;

        $listener = new UserSessionListener($this->mockScopeMatcher(), $tokenChecker);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
    }

    public function testDoesNotAddTheForceNoCacheIfThereIsNoSession(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->expects($this->never())
            ->method('hasBackendUser')
        ;

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), $tokenChecker);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
    }

    public function testDoesNotAddTheForceNoCacheIfThereIsNoAuthenticatedUser(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->setSession($this->mockSession());

        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->expects($this->once())
            ->method('hasBackendUser')
            ->willReturn(false)
        ;

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), $tokenChecker);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
        $this->assertNotNull($request->getSession());
    }
}
