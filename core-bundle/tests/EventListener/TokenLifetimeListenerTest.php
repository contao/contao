<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\TokenLifetimeListener;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Tests\TestCase;
use Contao\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TokenLifetimeListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\TokenLifetimeListener', $this->mockListener());
    }

    public function testReturnsImmediatelyItNotAMasterRequest(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->never())
            ->method('getToken')
        ;

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent(HttpKernelInterface::SUB_REQUEST));
    }

    public function testReturnsImmediatelyItNotAUsernamePasswordToken(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $token
            ->expects($this->never())
            ->method('getUser')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent());
    }

    public function testReturnsImmediatelyItNotAContaoUser(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);

        $token
            ->expects($this->never())
            ->method('hasAttribute')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent());
    }

    public function testSetsTheTokenLifetime(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->mockUser())
        ;

        $token
            ->expects($this->once())
            ->method('hasAttribute')
            ->with('lifetime')
            ->willReturn(false)
        ;

        $token
            ->expects($this->once())
            ->method('setAttribute')
            ->with('lifetime')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent());
    }

    public function testUpdatesTheTokenLifetime(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->mockUser())
        ;

        $token
            ->expects($this->once())
            ->method('hasAttribute')
            ->with('lifetime')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getAttribute')
            ->willReturn(time() + 3600)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent());
    }

    public function testRevokesTokenAfterInacitivty(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->mockUser('foobar'))
        ;

        $token
            ->expects($this->once())
            ->method('hasAttribute')
            ->with('lifetime')
            ->willReturn(true)
        ;

        $token
            ->expects($this->once())
            ->method('getAttribute')
            ->with('lifetime')
            ->willReturn(0)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with(null)
        ;

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token)
        ;

        $logger = $this->mockLoggerWithMessage('User "foobar" has been logged out automatically due to inactivity');

        $listener = $this->mockListener($tokenStorage, $logger);
        $listener->onKernelRequest($this->mockGetResponseEvent());
    }

    /**
     * Mocks a user object with an optional username.
     *
     * @param string|null $username
     *
     * @return User|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockUser(string $username = null): User
    {
        $user = $this->createPartialMock(User::class, ['getUsername']);

        if (null !== $username) {
            $user
                ->expects($this->once())
                ->method('getUsername')
                ->willReturn('foobar')
            ;
        }

        return $user;
    }

    /**
     * Mocks a get response event.
     *
     * @param int $requestType
     *
     * @return GetResponseEvent
     */
    private function mockGetResponseEvent(int $requestType = KernelInterface::MASTER_REQUEST): GetResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        $request = new Request();
        $request->attributes->set('_scope', 'backend');

        return new GetResponseEvent($kernel, $request, $requestType);
    }

    /**
     * Mocks the token lifetime listener.
     *
     * @param TokenStorageInterface|null $tokenStorage
     * @param LoggerInterface|null       $logger
     *
     * @return TokenLifetimeListener
     */
    private function mockListener(TokenStorageInterface $tokenStorage = null, LoggerInterface $logger = null): TokenLifetimeListener
    {
        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        if (null === $logger) {
            $logger = $this->createMock(LoggerInterface::class);
        }

        return new TokenLifetimeListener($tokenStorage, $this->mockScopeMatcher(), 3600, $logger);
    }

    /**
     * Mocks the logger service with a message.
     *
     * @param string $message
     *
     * @return LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockLoggerWithMessage(string $message): LoggerInterface
    {
        $context = [
            'contao' => new ContaoContext(
                'Contao\CoreBundle\EventListener\TokenLifetimeListener::onKernelRequest',
                ContaoContext::ACCESS
            ),
        ];

        $logger = $this->createMock(LoggerInterface::class);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with($message, $context)
        ;

        return $logger;
    }
}
