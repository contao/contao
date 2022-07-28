<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Security;

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\Security\SwitchUserListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserListenerTest extends TestCase
{
    public function testAddsALogEntryIfAUserSwitchesToAnotherUser(): void
    {
        $logger = $this->mockLogger('User "user1" has switched to user "user2"');
        $tokenStorage = $this->mockTokenStorage('user1');
        $event = $this->mockSwitchUserEvent('user2');

        $listener = new SwitchUserListener($tokenStorage, $logger);
        $listener($event);
    }

    public function testFailsIfTheTokenStorageDoesNotContainAToken(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(null)
        ;

        $event = new SwitchUserEvent(new Request(), $this->createMock(BackendUser::class));
        $listener = new SwitchUserListener($tokenStorage, $this->mockLogger());

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The token storage did not contain a token.');

        $listener($event);
    }

    /**
     * @return LoggerInterface&MockObject
     */
    private function mockLogger(string $message = null): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        if (null === $message) {
            return $logger;
        }

        $logger
            ->expects($this->once())
            ->method('info')
            ->with($message)
        ;

        return $logger;
    }

    /**
     * @return TokenStorageInterface&MockObject
     */
    private function mockTokenStorage(string $username = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        if (null !== $username) {
            $token = $this->createMock(TokenInterface::class);
            $token
                ->expects($this->once())
                ->method('getUserIdentifier')
                ->willReturn($username)
            ;

            $tokenStorage
                ->expects($this->once())
                ->method('getToken')
                ->willReturn($token)
            ;
        }

        return $tokenStorage;
    }

    private function mockSwitchUserEvent(string $username = null): SwitchUserEvent
    {
        $user = $this->createPartialMock(BackendUser::class, ['getUserIdentifier']);

        if (null !== $username) {
            $user
                ->expects($this->once())
                ->method('getUserIdentifier')
                ->willReturn($username)
            ;
        }

        return new SwitchUserEvent(new Request(), $user);
    }
}
