<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security;

use Contao\CoreBundle\Security\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TokenCheckerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $tokenChecker = new TokenChecker($this->createMock(SessionInterface::class));

        $this->assertInstanceOf('Contao\CoreBundle\Security\TokenChecker', $tokenChecker);
    }

    public function testAuthenticatesAUserFromTheSessionToken(): void
    {
        $token = new PreAuthenticatedToken('foobar', null, 'foobar', ['foobar']);
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('get')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(serialize($token))
        ;

        $tokenChecker = new TokenChecker($session);

        $this->assertTrue($tokenChecker->hasAuthenticatedToken(FrontendUser::SECURITY_SESSION_KEY));
    }

    public function testDoesNotAuthenticateIfTheSessionIsNotStarted(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(false)
        ;

        $tokenChecker = new TokenChecker($session);

        $this->assertFalse($tokenChecker->hasAuthenticatedToken(FrontendUser::SECURITY_SESSION_KEY));
    }

    public function testDoesNotAuthenticateIfTheSessionKeyDoesNotExist(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(false)
        ;

        $tokenChecker = new TokenChecker($session);

        $this->assertFalse($tokenChecker->hasAuthenticatedToken(FrontendUser::SECURITY_SESSION_KEY));
    }

    public function testChecksIfTheSessionContainsAToken(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('get')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(serialize(new \stdClass()))
        ;

        $tokenChecker = new TokenChecker($session);

        $this->assertFalse($tokenChecker->hasAuthenticatedToken(FrontendUser::SECURITY_SESSION_KEY));
    }

    public function testChecksIfTheSessionTokenIsAuthenticated(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('isAuthenticated')
            ->willReturn(false)
        ;

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('get')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(serialize($token))
        ;

        $tokenChecker = new TokenChecker($session);

        $this->assertFalse($tokenChecker->hasAuthenticatedToken(FrontendUser::SECURITY_SESSION_KEY));
    }

    public function testDoesNotReturnAUsernameIfTheSessionDoesNotContainAToken(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(false)
        ;

        $tokenChecker = new TokenChecker($session);

        $this->assertNull($tokenChecker->getUsername(FrontendUser::SECURITY_SESSION_KEY));
    }

    public function testReturnsTheUsernameFromTheSessionToken(): void
    {
        $user = $this->createMock(FrontendUser::class);

        $user
            ->expects($this->any())
            ->method('getUsername')
            ->willReturn('foobar')
        ;

        $token = new PreAuthenticatedToken($user, null, 'foobar', ['foobar']);
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('has')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('get')
            ->with(FrontendUser::SECURITY_SESSION_KEY)
            ->willReturn(serialize($token))
        ;

        $tokenChecker = new TokenChecker($session);

        $this->assertSame('foobar', $tokenChecker->getUsername(FrontendUser::SECURITY_SESSION_KEY));
    }
}
