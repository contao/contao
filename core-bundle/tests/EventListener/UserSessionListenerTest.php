<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\UserSessionListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;

class UserSessionListenerTest extends TestCase
{
    /**
     * @dataProvider scopeBagProvider
     */
    public function testReplacesTheSessionUponKernelRequest(string $scope, string $userClass, string $sessionBagName): void
    {
        $sessionValues = [
            'foo' => 'bar',
            'lonesome' => 'looser',
        ];

        $user = $this->mockClassWithProperties($userClass, ['session' => $sessionValues]);

        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->once())
            ->method('addListener')
            ->with(KernelEvents::RESPONSE)
        ;

        $session = $this->mockSession();

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', $scope);

        $listener = $this->mockListener(null, $tokenStorage, $eventDispatcher);
        $listener->onKernelRequest($this->mockGetResponseEvent($request));

        /** @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        $this->assertSame($sessionValues, $bag->all());
    }

    public function scopeBagProvider(): \Generator
    {
        yield [ContaoCoreBundle::SCOPE_BACKEND, BackendUser::class, 'contao_backend'];
        yield [ContaoCoreBundle::SCOPE_FRONTEND, FrontendUser::class, 'contao_frontend'];
    }

    /**
     * @dataProvider scopeTableProvider
     */
    public function testStoresTheSessionUponKernelResponse(string $scope, string $userClass, string $userTable): void
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

        $token = $this->createMock(UsernamePasswordToken::class);
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
        $request->setSession($this->mockSession());
        $request->attributes->set('_scope', $scope);

        $listener = $this->mockListener($connection, $tokenStorage);
        $listener->onKernelResponse($this->mockFilterResponseEvent($request));
    }

    public function scopeTableProvider(): \Generator
    {
        yield [ContaoCoreBundle::SCOPE_BACKEND, BackendUser::class, 'tl_user'];
        yield [ContaoCoreBundle::SCOPE_FRONTEND, FrontendUser::class, 'tl_member'];
    }

    /**
     * @dataProvider noUserProvider
     */
    public function testDoesNotReplaceTheSessionIfThereIsNoUser(AnonymousToken $token = null): void
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
            ->willReturn($token)
        ;

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener(null, $tokenStorage);
        $listener->onKernelRequest($this->mockGetResponseEvent($request));
    }

    /**
     * @dataProvider noUserProvider
     */
    public function testDoesNotStoreTheSessionIfThereIsNoUser(AnonymousToken $token = null): void
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
            ->willReturn($token)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener($connection, $tokenStorage);
        $listener->onKernelResponse($this->mockFilterResponseEvent($request));
    }

    public function noUserProvider(): \Generator
    {
        yield [null];
        yield [new AnonymousToken('key', 'anon.')];
    }

    public function testDoesNotReplaceTheSessionUponSubrequests(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener = $this->mockListener();
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
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, new Response());

        $listener = $this->mockListener($connection);
        $listener->onKernelResponse($event);
    }

    public function testDoesNotReplaceTheSessionIfNotAContaoRequest(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $request = new Request();
        $request->setSession($session);

        $listener = $this->mockListener();
        $listener->onKernelRequest($this->mockGetResponseEvent($request));
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

        $request = new Request();
        $request->setSession($session);

        $listener = $this->mockListener($connection);
        $listener->onKernelResponse($this->mockFilterResponseEvent($request));
    }

    public function testDoesNotReplaceTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);
        $token
            ->method('getUser')
            ->willReturn(new User('foo', 'bar'))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->mockListener($this->createMock(Connection::class), $tokenStorage);

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener->onKernelRequest($this->mockGetResponseEvent($request));

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    public function testDoesNotStoreTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $token = $this->createMock(UsernamePasswordToken::class);
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
        $listener = $this->mockListener($connection, $tokenStorage);

        $request = new Request();
        $request->setSession($this->mockSession());
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $listener->onKernelResponse($this->mockFilterResponseEvent($request));

        $this->addToAssertionCount(1);  // does not throw an exception
    }

    public function testFailsToReplaceTheSessionIfThereIsNoSession(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class, ['session' => []]);
        $token = $this->createMock(UsernamePasswordToken::class);
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
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener(null, $tokenStorage);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request did not contain a session.');

        $listener->onKernelRequest($this->mockGetResponseEvent($request));
    }

    public function testFailsToStoreTheSessionIfThereIsNoSession(): void
    {
        $user = $this->createPartialMock(BackendUser::class, ['getTable']);
        $user
            ->method('getTable')
            ->willReturn('tl_user')
        ;

        $token = $this->createMock(UsernamePasswordToken::class);
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
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener(null, $tokenStorage);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request did not contain a session.');

        $listener->onKernelResponse($this->mockFilterResponseEvent($request));
    }

    /**
     * Mocks a session listener.
     */
    private function mockListener(Connection $connection = null, TokenStorageInterface $tokenStorage = null, EventDispatcherInterface $eventDispatcher = null): UserSessionListener
    {
        if (null === $connection) {
            $connection = $this->createMock(Connection::class);
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);
        $scopeMatcher = $this->mockScopeMatcher();

        if (null === $eventDispatcher) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        }

        return new UserSessionListener($connection, $tokenStorage, $trustResolver, $scopeMatcher, $eventDispatcher);
    }

    private function mockGetResponseEvent(Request $request = null): GetResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        return new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
    }

    private function mockFilterResponseEvent(Request $request = null): FilterResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        return new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, new Response());
    }
}
