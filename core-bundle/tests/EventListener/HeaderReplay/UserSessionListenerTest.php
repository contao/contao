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

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\HeaderReplay\UserSessionListener;
use Contao\CoreBundle\Security\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
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
     * @param array $sessionKeys
     */
    public function testAddsTheForceNoCacheHeader(array $sessionKeys): void
    {
        $session = $this->mockSession();
        $session->setId('foobar-id');

        $request = new Request();
        $request->attributes->set('_scope', 'frontend');
        $request->setSession($session);

        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->expects($this->atLeastOnce())
            ->method('hasAuthenticatedToken')
            ->willReturnCallback(
                function (string $sessionKey) use ($sessionKeys): bool {
                    return $sessionKeys[$sessionKey];
                }
            )
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
        return [
            [[FrontendUser::SECURITY_SESSION_KEY => true, BackendUser::SECURITY_SESSION_KEY => false]],
            [[FrontendUser::SECURITY_SESSION_KEY => false, BackendUser::SECURITY_SESSION_KEY => true]],
        ];
    }

    public function testDoesNotAddTheForceNoCacheHeaderIfNotInContaoScope(): void
    {
        $event = new HeaderReplayEvent(new Request(), new ResponseHeaderBag());
        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->expects($this->any())
            ->method('hasAuthenticatedToken')
            ->willReturn(true)
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
            ->expects($this->any())
            ->method('hasAuthenticatedToken')
            ->willReturn(true)
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
            ->expects($this->any())
            ->method('hasAuthenticatedToken')
            ->willReturn(false)
        ;

        $event = new HeaderReplayEvent($request, new ResponseHeaderBag());

        $listener = new UserSessionListener($this->mockScopeMatcher(), $tokenChecker);
        $listener->onReplay($event);

        $this->assertArrayNotHasKey('t42-force-no-cache', $event->getHeaders()->all());
        $this->assertNotNull($request->getSession());
    }
}
