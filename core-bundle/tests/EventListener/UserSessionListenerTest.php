<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\UserSessionListener;
use Contao\CoreBundle\Test\TestCase;
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
 *
 * @author Yanick Witschi <https:/github.com/toflar>
 */
class UserSessionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\UserSessionListener', $this->getListener());
    }

    /**
     * Tests that the session is replaced upon kernel.request.
     *
     * @param string $scope
     * @param string $userClass
     * @param string $sessionBagName
     *
     * @dataProvider scopeBagProvider
     */
    public function testSessionReplacedOnKernelRequest($scope, $userClass, $sessionBagName)
    {
        $sessionValuesToBeSet = [
            'foo' => 'bar',
            'lonesome' => 'looser',
        ];

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $session = $this->mockSession();

        $user = $this
            ->getMockBuilder($userClass)
            ->setMethods(['__get'])
            ->getMock()
        ;

        $user
            ->expects($this->any())
            ->method('__get')
            ->with($this->equalTo('session'))
            ->willReturn($sessionValuesToBeSet)
        ;

        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->setContainer($this->mockContainerWithContaoScopes($scope));
        $listener->onKernelRequest($responseEvent);

        /** @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        $this->assertSame($sessionValuesToBeSet, $bag->all());
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
    public function testSessionStoredOnKernelResponse($scope, $userClass, $userTable)
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $connection = $this->getMock('Doctrine\DBAL\Connection', ['update'], [], '', false);

        $connection
            ->expects($this->once())
            ->method('update')
        ;

        $user = $this
            ->getMockBuilder($userClass)
            ->setMethods(['__get'])
            ->getMock()
        ;

        $user
            ->expects($this->any())
            ->method('getTable')
            ->willReturn($userTable)
        ;

        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->getListener($this->mockSession(), $connection, $tokenStorage);
        $listener->setContainer($this->mockContainerWithContaoScopes($scope));
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Tests that the session bag is not requested when there is no user.
     *
     * @param AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testListenerSkipIfNoUserOnKernelRequest(AnonymousToken $noUserReturn = null)
    {
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Tests that neither the session bag nor doctrine is requested when there is no user.
     *
     * @param AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testListenerSkipIfNoUserOnKernelResponse(AnonymousToken $noUserReturn = null)
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $connection = $this->getMock('Doctrine\DBAL\Connection', [], [], '', false);

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $connection
            ->expects($this->never())
            ->method('execute')
        ;

        $listener = $this->getListener($session, $connection, $tokenStorage);
        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Tests that the session bag is not requested upon a sub request.
     */
    public function testListenerSkipUponSubRequestOnKernelRequest()
    {
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $listener = $this->getListener($session);
        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Tests that neither the session bag nor doctrine is requested upon a sub request.
     */
    public function testListenerSkipUponSubRequestOnKernelResponse()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
            new Response()
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $connection = $this->getMock('Doctrine\DBAL\Connection', [], [], '', false);

        $connection
            ->expects($this->never())
            ->method('prepare')
        ;

        $connection
            ->expects($this->never())
            ->method('execute')
        ;

        $listener = $this->getListener($session, $connection);
        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Tests that the session bag is not requested if there is no Contao user upon kernel.request.
     */
    public function testListenerSkipIfNoContaoUserOnKernelRequest()
    {
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn(new User('foo', 'bar'))
        ;

        /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        /** @var UserSessionListener|\PHPUnit_Framework_MockObject_MockObject $listener */
        $listener = $this
            ->getMockBuilder('Contao\CoreBundle\EventListener\UserSessionListener')
            ->setConstructorArgs([
                $this->mockSession(),
                $this->getMock('Doctrine\DBAL\Connection', [], [], '', false),
                $tokenStorage,
                new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class),
            ])
            ->setMethods(['isContaoMasterRequest'])
            ->getMock()
        ;

        $listener
            ->expects($this->any())
            ->method('isContaoMasterRequest')
            ->willReturn(true)
        ;

        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Tests that neither the session bag nor doctrine is requested if there is no Contao user upon kernel.response.
     */
    public function testListenerSkipIfNoContaoUserOnKernelResponse()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn(new User('foo', 'bar'))
        ;

        /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject $tokenStorage */
        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        /** @var UserSessionListener|\PHPUnit_Framework_MockObject_MockObject $listener */
        $listener = $this
            ->getMockBuilder('Contao\CoreBundle\EventListener\UserSessionListener')
            ->setConstructorArgs([
                $this->mockSession(),
                $this->getMock('Doctrine\DBAL\Connection', [], [], '', false),
                $tokenStorage,
                new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class),
            ])
            ->setMethods(['isContaoMasterRequest'])
            ->getMock()
        ;

        $listener
            ->expects($this->any())
            ->method('isContaoMasterRequest')
            ->willReturn(true)
        ;

        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Provides the data for the testSessionReplacedOnKernelRequest() method.
     *
     * @return array
     */
    public function scopeBagProvider()
    {
        return [
            [ContaoCoreBundle::SCOPE_BACKEND, 'Contao\BackendUser', 'contao_backend'],
            [ContaoCoreBundle::SCOPE_FRONTEND, 'Contao\FrontendUser', 'contao_frontend'],
        ];
    }

    /**
     * Provides the data for the testSessionStoredOnKernelResponse() method.
     *
     * @return array
     */
    public function scopeTableProvider()
    {
        return [
            [ContaoCoreBundle::SCOPE_BACKEND, 'Contao\BackendUser', 'tl_user'],
            [ContaoCoreBundle::SCOPE_FRONTEND, 'Contao\FrontendUser', 'tl_member'],
        ];
    }

    /**
     * Provides the data for the user-less tests.
     *
     * @return array
     */
    public function noUserProvider()
    {
        return [
            [null],
            [new AnonymousToken('key', 'anon.')],
        ];
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
    private function getListener(SessionInterface $session = null, Connection $connection = null, TokenStorageInterface $tokenStorage = null)
    {
        if (null === $session) {
            $session = $this->mockSession();
        }

        if (null === $connection) {
            $connection = $this->getMock('Doctrine\DBAL\Connection', [], [], '', false);
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->getMock(
                'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
            );
        }

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);

        return new UserSessionListener($session, $connection, $tokenStorage, $trustResolver);
    }
}
