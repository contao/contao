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
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * Tests that the session bag is not requested when there is no user.
     *
     * @param AnonymousToken $noUserReturn The user token
     *
     * @dataProvider noUserProvider
     */
    public function testListenerSkipIfNoUserOnKernelRequest(AnonymousToken $noUserReturn = null)
    {
        $request = new Request();

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session\\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $tokenStorage = $this->getMock('Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface');

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->onKernelRequest($responseEvent);
    }

    /**
     * Tests that the session bag is never requested when there is no master request.
     */
    public function testListenerSkipIfNoMasterRequestOnKernelRequest()
    {
        $request = new Request();

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session\\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $listener = $this->getListener($session);
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
        $request  = new Request();
        $response = new Response();

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session\\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $tokenStorage = $this->getMock('Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface');

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $connection = $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false);

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
     * Tests that neither the session bag nor doctrine is requested when there is no master request.
     */
    public function testListenerSkipIfNoMasterRequestOnKernelResponse()
    {
        $request  = new Request();
        $response = new Response();

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $session = $this->getMock('Symfony\\Component\\HttpFoundation\\Session\\SessionInterface');

        $session
            ->expects($this->never())
            ->method('getBag')
        ;

        $connection = $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false);

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
     * Tests that session values are replaced upon kernel.request.
     *
     * @param string $scope          The container scope
     * @param string $userClass      The user class
     * @param string $sessionBagName The session bag
     *
     * @dataProvider scopeProvider
     */
    public function testSessionReplacedOnKernelRequest($scope, $userClass, $sessionBagName)
    {
        $sessionValuesToBeSet = [
            'foo'      => 'bar',
            'lonesome' => 'looser',
        ];

        $request = new Request();

        $responseEvent = new GetResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope($scope);

        $session = $this->mockSession();

        $user = $this->getMockBuilder($userClass)
            ->setMethods(['__get'])
            ->getMock()
        ;

        $user->expects($this->any())
            ->method('__get')
            ->with($this->equalTo('session'))
            ->willReturn($sessionValuesToBeSet)
        ;

        $token = $this->getMock('Contao\\CoreBundle\\Security\\Authentication\\ContaoToken', [], [], '', false);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->getMock('Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface');

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->getListener($session, null, $tokenStorage);
        $listener->setContainer($container);
        $listener->onKernelRequest($responseEvent);

        /* @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        $this->assertSame($sessionValuesToBeSet, $bag->all());
    }

    /**
     * Tests that the session values are replaced upon kernel.request.
     *
     * @param string  $scope           The container scope
     * @param string  $sessionBagName  The session bag name
     * @param Request $request         The request object
     * @param array   $currentReferer  The current referer
     * @param array   $expectedReferer The expected referer
     *
     * @dataProvider sessionStoredOnKernelResponseProvider
     */
    public function testSessionStoredOnKernelResponse(
        $scope,
        $sessionBagName,
        $userClass,
        $userTable,
        Request $request,
        $refererKey,
        $currentReferer,
        $expectedReferer
    ) {
        $response = new Response();

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $connection = $this->getMock('Doctrine\\DBAL\\Connection', ['prepare', 'execute'], [], '', false);

        $connection
            ->expects($this->any())
            ->method('prepare')
            ->willReturnSelf()
        ;

        $connection
            ->expects($this->any())
            ->method('execute')
        ;

        $user = $this->getMockBuilder($userClass)
            ->setMethods(['__get'])
            ->getMock()
        ;

        $user
            ->expects($this->any())
            ->method('getTable')
            ->willReturn($userTable)
        ;

        $token = $this->getMock('Contao\\CoreBundle\\Security\\Authentication\\ContaoToken', [], [], '', false);

        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->getMock('Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface');

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $container = $this->mockContainerWithContaoScopes();
        $container->enterScope($scope);

        $session = $this->mockSession();

        /* @var AttributeBagInterface $bag */
        $bag = $session->getBag($sessionBagName);

        // Set the current referer URLs
        $bag->set($refererKey, $currentReferer);

        $listener = $this->getListener($session, $connection, $tokenStorage);
        $listener->setContainer($container);
        $listener->onKernelResponse($responseEvent);

        $this->assertSame($expectedReferer, $bag->get($refererKey));
    }

    /**
     * Provides the data for the kernel.response tests.
     *
     * @return array The test data
     */
    public function sessionStoredOnKernelResponseProvider()
    {
        $request = new Request();
        $request->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $request->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');

        $requestWithRefInUrl = new Request();
        $requestWithRefInUrl->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $requestWithRefInUrl->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');
        $requestWithRefInUrl->query->set('ref', 'dummyTestRefererId');

        return [
            'Test current referer null returns correct new referer for back end scope' => [
                ContaoCoreBundle::SCOPE_BACKEND,
                'contao_backend',
                'Contao\\BackendUser',
                'tl_user',
                $request,
                'referer',
                null,
                [
                    'dummyTestRefererId' => [
                        'last'    => '',
                        'current' => 'path/of/contao?having&query&string=1',
                    ],
                ],
            ],
            'Test referer returns correct new referer for back end scope' => [
                ContaoCoreBundle::SCOPE_BACKEND,
                'contao_backend',
                'Contao\\BackendUser',
                'tl_user',
                $requestWithRefInUrl,
                'referer',
                [
                    'dummyTestRefererId' => [
                        'last'    => '',
                        'current' => 'hi/I/am/your_current_referer.html',
                    ],
                ],
                [
                    'dummyTestRefererId' => [
                        'last'    => 'hi/I/am/your_current_referer.html',
                        'current' => 'path/of/contao?having&query&string=1',
                    ],
                ],
            ],
            'Test current referer null returns null for front end scope' => [
                ContaoCoreBundle::SCOPE_FRONTEND,
                'contao_frontend',
                'Contao\\FrontendUser',
                'tl_member',
                $request,
                'referer',
                null,
                null,
            ],
            'Test referer returns correct new referer for front end scope' => [
                ContaoCoreBundle::SCOPE_FRONTEND,
                'contao_frontend',
                'Contao\\FrontendUser',
                'tl_member',
                $requestWithRefInUrl,
                'referer',
                [
                    'last'    => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                ],
                [
                    'last'    => 'hi/I/am/your_current_referer.html',
                    'current' => 'path/of/contao?having&query&string=1',
                ],
            ],
        ];
    }

    /**
     * Provides the data for the user-less tests.
     *
     * @return array The test data
     */
    public function noUserProvider()
    {
        $anonymousToken = new AnonymousToken('key', 'anon.');

        return [
            [null],
            [$anonymousToken],
        ];
    }

    /**
     * Provides the data for the scope-based tests.
     *
     * @return array the test data
     */
    public function scopeProvider()
    {
        return [
            [ContaoCoreBundle::SCOPE_BACKEND, 'Contao\\BackendUser', 'contao_backend'],
            [ContaoCoreBundle::SCOPE_FRONTEND, 'Contao\\FrontendUser', 'contao_frontend'],
        ];
    }

    /**
     * Returns the session listener object.
     *
     * @param SessionInterface      $session      The session object
     * @param Connection            $connection   The database connection
     * @param TokenStorageInterface $tokenStorage The token storage object
     *
     * @return UserSessionListener The session listener object
     */
    private function getListener(
        SessionInterface $session = null,
        Connection $connection = null,
        TokenStorageInterface $tokenStorage = null
    ) {
        if (null === $session) {
            $session = $this->mockSession();
        }

        if (null === $connection) {
            $connection = $this->getMock('Doctrine\\DBAL\\Connection', [], [], '', false);
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->getMock('Symfony\\Component\\Security\\Core\\Authentication\\Token\\Storage\\TokenStorageInterface');
        }

        return new UserSessionListener($session, $connection, $tokenStorage);
    }
}
