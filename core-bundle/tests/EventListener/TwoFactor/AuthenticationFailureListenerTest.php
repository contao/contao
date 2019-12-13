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
use Contao\CoreBundle\EventListener\TwoFactor\AuthenticationFailureListener;
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
class AuthenticationFailureListenerTest extends ContaoTestCase
{
    public function testReturnsIfTheTokenIsNotATwoFactorToken(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->never())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $listener = new AuthenticationFailureListener(300, 3);
        $listener->onAuthenticationFailure(new TwoFactorAuthenticationEvent(new Request(), $token));
    }

    public function testReturnsIfTheUserIsNotAContaoUser(): void
    {
        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $listener = new AuthenticationFailureListener(300, 3);
        $listener->onAuthenticationFailure(new TwoFactorAuthenticationEvent(new Request(), $token));
    }

    /**
     * @dataProvider getUserData
     */
    public function testDecreasesLoginCount(string $class): void
    {
        /** @var User&MockObject $user */
        $user = $this->mockClassWithProperties($class);
        $user->loginCount = 3;

        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $listener = new AuthenticationFailureListener(300, 3);
        $listener->onAuthenticationFailure(new TwoFactorAuthenticationEvent(new Request(), $token));

        $this->assertSame(2, $user->loginCount);
    }

    /**
     * @dataProvider getUserData
     */
    public function testIncreasesLockTimeWithLockPeriod(string $class): void
    {
        ClockMock::register(AuthenticationFailureListener::class);

        /** @var User&MockObject $user */
        $user = $this->mockClassWithProperties($class);
        $user->loginCount = 1;

        $token = $this->createMock(TwoFactorToken::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $listener = new AuthenticationFailureListener(300, 3);
        $listener->onAuthenticationFailure(new TwoFactorAuthenticationEvent(new Request(), $token));

        $this->assertSame(time() + 300, $user->locked);
        $this->assertSame(3, $user->loginCount);
    }

    public function getUserData(): \Generator
    {
        yield [FrontendUser::class];
        yield [BackendUser::class];
    }
}
