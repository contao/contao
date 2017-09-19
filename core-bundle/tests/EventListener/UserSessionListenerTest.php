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
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\User;

/**
 * Tests the UserSessionListener class.
 */
class UserSessionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\UserSessionListener', $this->getListener());
    }

    /**
     * Tests replacing the session upon kernel.request.
     *
     * @param string $scope
     * @param string $userClass
     * @param string $sessionBagName
     *
     * @dataProvider scopeBagProvider
     */
    public function testReplacesTheSessionUponKernelRequest(string $scope, string $userClass, string $sessionBagName): void
    {
        $sessionValuesToBeSet = [
            'foo' => 'bar',
            'lonesome' => 'looser',
        ];

        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $session = $this->mockSession();

        $user = $this
            ->getMockBuilder($userClass)
            ->setMethods(['__get'])
            ->getMock()
        ;

        $user
            ->method('__get')
            ->with($this->equalTo('session'))
            ->willReturn($sessionValuesToBeSet)
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

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->onKernelRequest($responseEvent);

        /** @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        $this->assertSame($sessionValuesToBeSet, $bag->all());
    }

    /**
     * Provides the data for the testSessionReplacedOnKernelRequest() method.
     *
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
     * Tests that the session is stored upon kernel.response.
     *
     * @param string $scope
     * @param string $userClass
     * @param string $userTable
     *
     * @dataProvider scopeTableProvider
     */
    public function testStoresTheSessionUponKernelResponse($scope, $userClass, $userTable): void
    {
        $request = new Request();
        $request->attributes->set('_scope', $scope);

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->once())
            ->method('update')
        ;

        $user = $this
            ->getMockBuilder($userClass)
            ->setMethods(['__get', 'getTable'])
            ->getMock()
        ;

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

        $listener = $this->getListener($this->mockSession(), $connection, $tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Provides the data for the testSessionStoredOnKernelResponse() method.
     *
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
     * Tests that the session bag is not requested when there is no user.
     *
     * @param AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testDoesNotReplaceTheSessionIfThereIsNoUser(AnonymousToken $noUserReturn = null): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

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

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Tests that neither the session bag nor doctrine is requested when there is no user.
     *
     * @param AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testDoesNotStoreTheSessionIfThereIsNoUser(AnonymousToken $noUserReturn = null): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

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

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepare', 'execute'])
            ->getMock()
        ;

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $connection
            ->expects($this->never())
            ->method('execute')
        ;

        $listener = $this->getListener($session, $connection, $tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Provides the data for the user-less tests.
     *
     * @return array
     */
    public function noUserProvider(): array
    {
        return [
            [null],
            [new AnonymousToken('key', 'anon.')],
        ];
    }

    /**
     * Tests that the session bag is not requested upon a sub request.
     */
    public function testDoesNotReplaceTheSessionUponSubrequests(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $listener = $this->getListener($session);
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Tests that neither the session bag nor doctrine is requested upon a subrequest.
     */
    public function testDoesNotStoreTheSessionUponSubrequests(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new Response()
        );

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $connection = $this
            ->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['prepare', 'execute'])
            ->getMock()
        ;

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $connection
            ->expects($this->never())
            ->method('execute')
        ;

        $listener = $this->getListener($session, $connection);
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Tests that the session bag is not requested if there is no Contao user upon kernel.request.
     */
    public function testDoesNotReplaceTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

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

        $listener = $this->getListener(
            $this->mockSession(),
            $this->createMock(Connection::class),
            $tokenStorage
        );

        $listener->onKernelRequest($responseEvent);

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * Tests that neither the session bag nor doctrine is requested if there is no Contao user upon kernel.response.
     */
    public function testDoesNotStoreTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

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

        $listener = $this->getListener(
            $this->mockSession(),
            $this->createMock(Connection::class),
            $tokenStorage
        );

        $listener->onKernelResponse($responseEvent);

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    /**
     * Returns the session listener object.
     *
     * @param SessionInterface      $session
     * @param Connection            $connection
     * @param TokenStorageInterface $tokenStorage
     *
     * @return UserSessionListener
     */
    private function getListener(SessionInterface $session = null, Connection $connection = null, TokenStorageInterface $tokenStorage = null): UserSessionListener
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
}
