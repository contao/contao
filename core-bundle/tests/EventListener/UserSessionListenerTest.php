<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\UserSessionListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

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
        $listener = $this->getListener();

        $this->assertInstanceOf('Contao\\CoreBundle\\EventListener\\UserSessionListener', $listener);
    }


    /**
     * Test session bag is never requested when having no user on kernel.request.
     *
     * @param   null|AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testListenerSkipIfNoUserOnKernelRequest($noUserReturn)
    {
        $request = new Request();
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->never())->method('getBag');

        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->once())->method('getToken')->willReturn($noUserReturn);

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Test session bag is never requested when having no master request on
     * kernel.request.
     */
    public function testListenerSkipIfNoMasterRequestOnKernelRequest()
    {
        $request = new Request();
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->never())->method('getBag');

        $listener = $this->getListener($session);
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Test neither session bag nor doctrine is requested when
     * having no user on kernel.response.
     *
     * @param   null|AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testListenerSkipIfNoUserOnKernelResponse($noUserReturn)
    {
        $request = new Request();
        $response = new Response();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');
        $session->expects($this->never())->method('getBag');

        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->once())->method('getToken')->willReturn($noUserReturn);

        $connection = $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false);
        $connection->expects($this->never())->method('prepare');
        $connection->expects($this->never())->method('execute');

        $listener = $this->getListener($session, $connection, $tokenStorage);

        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Test neither session bag nor doctrine is requested when
     * having no master request on kernel.response.
     */
    public function testListenerSkipIfNoMasterRequestOnKernelResponse()
    {
        $request = new Request();
        $response = new Response();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );
        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session\\SessionInterface');
        $session->expects($this->never())->method('getBag');
        $connection = $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false);
        $connection->expects($this->never())->method('prepare');
        $connection->expects($this->never())->method('execute');

        $listener = $this->getListener($session, $connection);

        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Tests that session values are replaced on kernel.request for both,
     * front end scope and back end scope.
     *
     * @param string $scope
     * @param string $userClass
     * @param string $sessionBagName
     *
     * @dataProvider scopeProvider
     */
    public function testSessionReplacedOnKernelRequest($scope, $userClass, $sessionBagName)
    {
        $sessionValuesToBeSet = [
            'foo'       => 'bar',
            'lonesome'  => 'looser'
        ];

        $request = new Request();
        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope($scope);
        $session = $this->mockSession();

        $user = $this->getMockBuilder($userClass)
            ->setMethods(['__get'])
            ->getMock();
        $user->expects($this->any())->method('__get')->with($this->equalTo('session'))->willReturn($sessionValuesToBeSet);

        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);
        $token->expects($this->any())->method('getUser')->willReturn($user);
        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->any())->method('getToken')->willReturn($token);

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->setContainer($container);
        $listener->onKernelRequest($responseEvent);

        /* @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        $this->assertSame($sessionValuesToBeSet, $bag->all());
    }


    /**
     * Tests that session values are replaced on kernel.request for both,
     * front end scope and back end scope.
     *
     * @param string     $scope
     * @param string     $sessionBagName
     * @param Request    $request
     * @param array|null $currentReferer
     * @param array|null $expectedReferer
     *
     * @dataProvider sessionStoredOnKernelResponseProvider
     */
    public function testSessionStoredOnKernelResponse(
        $scope,
        $sessionBagName,
        $userClass,
        $userTable,
        Request $request,
        $currentReferer,
        $expectedReferer
    ) {
        $response = new Response();
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $connection = $this->getMock('Doctrine\\DBAL\\Connection', ['prepare', 'execute'], [], '', false);
        $connection->expects($this->any())->method('prepare')->willReturnSelf();
        $connection->expects($this->any())->method('execute');

        $user = $this->getMockBuilder($userClass)
            ->setMethods(['__get'])
            ->getMock();
        $user->expects($this->any())->method('getTable')->willReturn($userTable);
        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);
        $token->expects($this->any())->method('getUser')->willReturn($user);
        $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->any())->method('getToken')->willReturn($token);
        $refererKey = $request->query->has('popup') ? 'popupReferer' : 'referer';
        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope($scope);
        $session = $this->mockSession();
        /* @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        // Set current referer values
        $bag->set($refererKey, $currentReferer);

        $listener = $this->getListener($session, $connection, $tokenStorage);
        $listener->setContainer($container);
        $listener->onKernelResponse($responseEvent);

        $this->assertSame($expectedReferer, $bag->get($refererKey));
    }

    public function sessionStoredOnKernelResponseProvider()
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $request->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');

        return [
            'Test current referer null returns correct new referer for back end scope' => [
                ContaoCoreBundle::SCOPE_BACKEND,
                'contao_backend',
                'Contao\\BackendUser',
                'tl_user',
                $request,
                null,
                [
                    'dummyTestRefererId' => [
                        'last'      => '',
                        // Make sure this one never contains a / at the beginning
                        'current'   => 'path/of/contao?having&query&string=1'
                    ]
                ]
            ]
        ];
    }

    public function noUserProvider()
    {
        $anonymousToken = new AnonymousToken('key', 'anon.');
        return [
            [null],
            [$anonymousToken]
        ];
    }

    public function scopeProvider()
    {
        return [
            [ContaoCoreBundle::SCOPE_BACKEND, 'Contao\\BackendUser', 'contao_backend'],
            [ContaoCoreBundle::SCOPE_FRONTEND, 'Contao\\FrontendUser', 'contao_frontend']
        ];
    }

    private function getListener(
        $session = null,
        $connection = null,
        $tokenStorage = null)
    {
        if (null === $session) {
            $session = $this->mockSession();
        }

        if (null === $connection) {
            $connection = $this->getMock(
                'Doctrine\\DBAL\\Connection',
                [],
                [],
                '',
                false
            );
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        }

        return new UserSessionListener($session, $connection, $tokenStorage);
    }
}
