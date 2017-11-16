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

use Contao\BackendUser;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\UserSessionListener;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\User;

class UserSessionListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = $this->mockListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\UserSessionListener', $listener);
    }

    /**
     * @param string $scope
     * @param string $userClass
     * @param string $sessionBagName
     *
     * @dataProvider scopeBagProvider
     */
    public function testReplacesTheSessionUponKernelRequest(string $scope, string $userClass, string $sessionBagName): void
    {
        $sessionValues = [
            'foo' => 'bar',
            'lonesome' => 'looser',
        ];

        $user = $this->mockClassWithProperties($userClass, ['session' => $sessionValues]);
        $token = $this->createMock(ContaoToken::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $session = $this->mockSession();

        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $listener = $this->mockListener($session, null, $tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent($request));

        /** @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        $this->assertSame($sessionValues, $bag->all());
    }

    /**
     * @return array
     */
    public function scopeBagProvider(): array
    {
        return [
            [ContaoCoreBundle::SCOPE_BACKEND, BackendUser::class, 'contao_backend'],
            [ContaoCoreBundle::SCOPE_FRONTEND, FrontendUser::class, 'contao_frontend'],
        ];
    }

    /**
     * @param string $scope
     * @param string $userClass
     * @param string $userTable
     *
     * @dataProvider scopeTableProvider
     */
    public function testStoresTheSessionUponKernelResponse($scope, $userClass, $userTable): void
    {
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('update')
        ;

        $user = $this->createPartialMock($userClass, ['getTable']);

        $user
            ->method('getTable')
            ->willReturn($userTable)
        ;

        $token = $this->createMock(ContaoToken::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $listener = $this->mockListener($this->mockSession(), $connection, $tokenStorage);
        $listener->onKernelResponse($this->mockFilterResponseEvent($request));
    }

    /**
     * @return array
     */
    public function scopeTableProvider(): array
    {
        return [
            [ContaoCoreBundle::SCOPE_BACKEND, BackendUser::class, 'tl_user'],
            [ContaoCoreBundle::SCOPE_FRONTEND, FrontendUser::class, 'tl_member'],
        ];
    }

    /**
     * @param AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testDoesNotReplaceTheSessionIfThereIsNoUser(AnonymousToken $noUserReturn = null): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener($session, null, $tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent($request));
    }

    /**
     * @param AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testDoesNotStoreTheSessionIfThereIsNoUser(AnonymousToken $noUserReturn = null): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener($session, $connection, $tokenStorage);
        $listener->onKernelResponse($this->mockFilterResponseEvent($request));
    }

    /**
     * @return array
     */
    public function noUserProvider(): array
    {
        return [
            [null],
            [new AnonymousToken('key', 'anon.')],
        ];
    }

    public function testDoesNotReplaceTheSessionUponSubrequests(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener = $this->mockListener($session);
        $listener->onKernelRequest($event);
    }

    public function testDoesNotStoreTheSessionUponSubrequests(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, new Response());

        $listener = $this->mockListener($session, $connection);
        $listener->onKernelResponse($event);
    }

    public function testDoesNotReplaceTheSessionIfNotAContaoRequest(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $listener = $this->mockListener($session);
        $listener->onKernelRequest($this->mockGetResponseEvent());
    }

    public function testDoesNotStoreTheSessionIfNotAContaoRequest(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $listener = $this->mockListener($session, $connection);
        $listener->onKernelResponse($this->mockFilterResponseEvent());
    }

    public function testDoesNotReplaceTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $token = $this->createMock(ContaoToken::class);

        $token
            ->method('getUser')
            ->willReturn(new User('foo', 'bar'))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->mockListener(
            $this->mockSession(),
            $this->createMock(Connection::class),
            $tokenStorage
        );

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener->onKernelRequest($this->mockGetResponseEvent($request));

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    public function testDoesNotStoreTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $token = $this->createMock(ContaoToken::class);

        $token
            ->method('getUser')
            ->willReturn(new User('foo', 'bar'))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $connection = $this->createMock(Connection::class);
        $listener = $this->mockListener($this->mockSession(), $connection, $tokenStorage);

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $listener->onKernelResponse($this->mockFilterResponseEvent($request));

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * Mocks a session listener.
     *
     * @param SessionInterface      $session
     * @param Connection            $connection
     * @param TokenStorageInterface $tokenStorage
     *
     * @return UserSessionListener
     */
    private function mockListener(SessionInterface $session = null, Connection $connection = null, TokenStorageInterface $tokenStorage = null): UserSessionListener
    {
        if (null === $session) {
            $session = $this->mockSession();
        }

        if (null === $connection) {
            $connection = $this->createMock(Connection::class);
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);

        return new UserSessionListener($session, $connection, $tokenStorage, $trustResolver, $this->mockScopeMatcher());
    }

    /**
     * Mocks a get response event.
     *
     * @param Request|null $request
     *
     * @return GetResponseEvent
     */
    private function mockGetResponseEvent(Request $request = null): GetResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        return new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
    }

    /**
     * Mocks a filter response event.
     *
     * @param Request|null $request
     *
     * @return FilterResponseEvent
     */
    private function mockFilterResponseEvent(Request $request = null): FilterResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        return new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, new Response());
    }
}
