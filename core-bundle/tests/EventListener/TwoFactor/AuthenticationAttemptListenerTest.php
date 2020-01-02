<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\TwoFactor;

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\TwoFactor\AuthenticationAttemptListener;
use Contao\CoreBundle\Security\Exception\LockedException;
use Contao\FrontendUser;
use Contao\TestCase\ContaoTestCase;
use Contao\User;
use PHPUnit\Framework\MockObject\MockObject;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorToken;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @group time-sensitive
 */
class AuthenticationAttemptListenerTest extends ContaoTestCase
{
    public function testReturnsIfTheTokenIsNotATwoFactorToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->never())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $listener = new AuthenticationAttemptListener();
        $listener(new TwoFactorAuthenticationEvent(new Request(), $token));
    }

    public function testReturnsIfTheUserIsNotAContaoUser(): void
    {
        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $listener = new AuthenticationAttemptListener();
        $listener(new TwoFactorAuthenticationEvent(new Request(), $token));
    }

    /**
     * @dataProvider getUserData
     */
    public function testReturnsIfTheLockPeriodHasExpired(string $class): void
    {
        ClockMock::register(AuthenticationAttemptListener::class);

        /** @var User&MockObject $user */
        $user = $this->mockClassWithProperties($class);
        $user->locked = time();

        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $listener = new AuthenticationAttemptListener();
        $listener(new TwoFactorAuthenticationEvent(new Request(), $token));
    }

    /**
     * @dataProvider getUserData
     */
    public function testThrowsAnExceptionIfTheLockPeriodIsStillActive(string $class): void
    {
        ClockMock::register(AuthenticationAttemptListener::class);

        /** @var User&MockObject $user */
        $user = $this->mockClassWithProperties($class);
        $user->locked = time() + 5;
        $user->username = 'foobar';

        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $listener = new AuthenticationAttemptListener();

        $this->expectException(LockedException::class);
        $this->expectExceptionMessage('User "foobar" has been locked for 5 seconds');

        $listener(new TwoFactorAuthenticationEvent(new Request(), $token));
    }

    public function getUserData(): \Generator
    {
        yield [FrontendUser::class];
        yield [BackendUser::class];
    }
}
