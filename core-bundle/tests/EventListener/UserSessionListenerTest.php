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
use Contao\User;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

class UserSessionListenerTest extends TestCase
{
    /**
     * @param class-string<User> $userClass
     *
     * @dataProvider scopeBagProvider
     */
    public function testReplacesTheSessionUponKernelRequest(string $scope, string $userClass, string $sessionBagName): void
    {
        $sessionValues = [
            'foo' => 'bar',
            'lonesome' => 'looser',
        ];

        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties($userClass);
        $user->session = $sessionValues;

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
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

        $listener = $this->getListener(null, $security, $eventDispatcher);
        $listener($this->getRequestEvent($request));

        $bag = $session->getBag($sessionBagName);

        $this->assertInstanceOf(AttributeBagInterface::class, $bag);
        $this->assertSame($sessionValues, $bag->all());
    }

    public function scopeBagProvider(): \Generator
    {
        yield [ContaoCoreBundle::SCOPE_BACKEND, BackendUser::class, 'contao_backend'];
        yield [ContaoCoreBundle::SCOPE_FRONTEND, FrontendUser::class, 'contao_frontend'];
    }

    /**
     * @param class-string<User> $userClass
     *
     * @dataProvider scopeTableProvider
     */
    public function testStoresTheSessionUpwrite(string $scope, string $userClass, string $userTable): void
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

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = new Request();
        $request->setSession($this->mockSession());

        $request->attributes->set('_scope', $scope);

        $listener = $this->getListener($connection, $security);
        $listener->write($this->getResponseEvent($request));
    }

    public function scopeTableProvider(): \Generator
    {
        yield [ContaoCoreBundle::SCOPE_BACKEND, BackendUser::class, 'tl_user'];
        yield [ContaoCoreBundle::SCOPE_FRONTEND, FrontendUser::class, 'tl_member'];
    }

    public function testDoesNotReplaceTheSessionIfThereIsNoUser(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn(null)
        ;

        $request = new Request();
        $request->setSession($session);

        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->getListener(null, $security);
        $listener($this->getRequestEvent($request));
    }

    public function testDoesNotStoreTheSessionIfThereIsNoUser(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn(null)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('update')
        ;

        $request = new Request();
        $request->setSession($session);

        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->getListener($connection, $security);
        $listener->write($this->getResponseEvent($request));
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
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener = $this->getListener();
        $listener($event);
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
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, new Response());

        $listener = $this->getListener($connection);
        $listener->write($event);
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

        $listener = $this->getListener();
        $listener($this->getRequestEvent($request));
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

        $listener = $this->getListener($connection);
        $listener->write($this->getResponseEvent($request));
    }

    public function testDoesNotReplaceTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn(new InMemoryUser('foo', 'bar'))
        ;

        $listener = $this->getListener($this->createMock(Connection::class), $security);

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener($this->getRequestEvent($request));

        $this->addToAssertionCount(1); // does not throw an exception
    }

    public function testDoesNotStoreTheSessionIfTheUserIsNotAContaoUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn(new InMemoryUser('foo', 'bar'))
        ;

        $connection = $this->createMock(Connection::class);
        $listener = $this->getListener($connection, $security);

        $request = new Request();
        $request->setSession($this->mockSession());

        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $listener->write($this->getResponseEvent($request));

        $this->addToAssertionCount(1); // does not throw an exception
    }

    public function testFailsToReplaceTheSessionIfThereIsNoSession(): void
    {
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->session = [];

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->getListener(null, $security);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request did not contain a session.');

        $listener($this->getRequestEvent($request));
    }

    public function testFailsToStoreTheSessionIfThereIsNoSession(): void
    {
        $user = $this->createPartialMock(BackendUser::class, ['getTable']);
        $user
            ->method('getTable')
            ->willReturn('tl_user')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->getListener(null, $security);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request did not contain a session.');

        $listener->write($this->getResponseEvent($request));
    }

    private function getListener(Connection|null $connection = null, Security|null $security = null, EventDispatcherInterface|null $eventDispatcher = null): UserSessionListener
    {
        $connection ??= $this->createMock(Connection::class);
        $security ??= $this->createMock(Security::class);
        $scopeMatcher = $this->mockScopeMatcher();
        $eventDispatcher ??= $this->createMock(EventDispatcherInterface::class);

        return new UserSessionListener($connection, $security, $scopeMatcher, $eventDispatcher);
    }

    private function getRequestEvent(Request|null $request = null): RequestEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        return new RequestEvent($kernel, $request ?? new Request(), HttpKernelInterface::MAIN_REQUEST);
    }

    private function getResponseEvent(Request|null $request = null): ResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        return new ResponseEvent($kernel, $request ?? new Request(), HttpKernelInterface::MAIN_REQUEST, new Response());
    }
}
