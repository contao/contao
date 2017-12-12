<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\Security\User;

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\SwitchUserListener;
use Contao\CoreBundle\Monolog\ContaoContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new SwitchUserListener($this->mockTokenStorage(), $this->mockLogger());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\SwitchUserListener', $listener);
    }

    public function testAddsALogEntryIfAUserSwitchesToAnotherUser(): void
    {
        $logger = $this->mockLogger('User "user1" has switched to user "user2"');
        $tokenStorage = $this->mockTokenStorage('user1');
        $event = $this->mockSwitchUserEvent('user2');

        $listener = new SwitchUserListener($tokenStorage, $logger);
        $listener->onSwitchUser($event);
    }

    /**
     * Mocks the logger service with an optional message.
     *
     * @param string|null $message
     *
     * @return LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockLogger(string $message = null): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);

        if (null === $message) {
            return $logger;
        }

        $context = [
            'contao' => new ContaoContext(
                'Contao\CoreBundle\EventListener\SwitchUserListener::onSwitchUser',
                ContaoContext::ACCESS
            ),
        ];

        $logger
            ->expects($this->once())
            ->method('info')
            ->with($message, $context)
        ;

        return $logger;
    }

    /**
     * Mocks a token storage with an optional username.
     *
     * @param string|null $username
     *
     * @return TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTokenStorage(string $username = null): TokenStorageInterface
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        if (null !== $username) {
            $token = $this->createMock(TokenInterface::class);

            $token
                ->expects($this->once())
                ->method('getUser')
                ->willReturn($this->mockBackendUser($username))
            ;

            $tokenStorage
                ->expects($this->once())
                ->method('getToken')
                ->willReturn($token)
            ;
        }

        return $tokenStorage;
    }

    /**
     * Mocks a back end user with an optional username.
     *
     * @param string|null $username
     *
     * @return BackendUser|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockBackendUser(string $username = null): BackendUser
    {
        $user = $this->createPartialMock(BackendUser::class, ['getUsername']);

        if (null !== $username) {
            $user
                ->expects($this->once())
                ->method('getUsername')
                ->willReturn($username)
            ;
        }

        return $user;
    }

    /**
     * Mocks the SwitchUserEvent with an optional target username.
     *
     * @param string|null $username
     *
     * @return SwitchUserEvent
     */
    private function mockSwitchUserEvent(string $username = null): SwitchUserEvent
    {
        /** @var UserInterface|\PHPUnit_Framework_MockObject_MockObject $user */
        $user = $this->createPartialMock(BackendUser::class, ['getUsername']);

        if (null !== $username) {
            $user
                ->expects($this->once())
                ->method('getUsername')
                ->willReturn($username)
            ;
        }

        return new SwitchUserEvent(new Request(), $user);
    }
}
