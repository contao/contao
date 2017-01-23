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
use Contao\CoreBundle\EventListener\StoreRefererListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Tests the StoreRefererListener class.
 *
 * @author Yanick Witschi <https:/github.com/toflar>
 */
class StoreRefererListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\StoreRefererListener', $this->getListener());
    }

    /**
     * Tests that the referer is stored upon kernel.response.
     *
     * @param string  $scope
     * @param Request $request
     * @param array   $currentReferer
     * @param array   $expectedReferer
     *
     * @dataProvider refererStoredOnKernelResponseProvider
     */
    public function testRefererStoredOnKernelResponse($scope, Request $request, $currentReferer, $expectedReferer)
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);

        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $container = $this->mockContainerWithContaoScopes($scope);

        // Set the current referer URLs
        $session = $this->mockSession();
        $session->set('referer', $currentReferer);

        $listener = $this->getListener($session, $tokenStorage);
        $listener->setContainer($container);
        $listener->onKernelResponse($responseEvent);

        $this->assertSame($expectedReferer, $session->get('referer'));
    }

    /**
     * Tests that the session is not written when there is no user.
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
            ->method('set')
        ;

        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $listener = $this->getListener($session, $tokenStorage);
        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Tests that the session is not written upon a sub request.
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
            ->method('set')
        ;

        $listener = $this->getListener($session);
        $listener->setContainer($this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND));
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Tests that the session is not written if the back end session cannot be modified.
     */
    public function testListenerSkipIfBackendSessionNotModifiable()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $container = $this->mockContainerWithContaoScopes(ContaoCoreBundle::SCOPE_BACKEND);
        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $session
            ->expects($this->never())
            ->method('set')
        ;

        $token = $this->getMock('Contao\CoreBundle\Security\Authentication\ContaoToken', [], [], '', false);

        $tokenStorage = $this->getMock(
            'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
        );

        $tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token)
        ;

        $listener = $this->getListener($session, $tokenStorage);
        $listener->setContainer($container);
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Provides the data for the testRefererStoredOnKernelResponse() method.
     *
     * @return array
     */
    public function refererStoredOnKernelResponseProvider()
    {
        $request = new Request();
        $request->attributes->set('_route', 'contao_backend');
        $request->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $request->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');

        $requestFrontend = clone $request;
        $requestFrontend->attributes->set('_route', 'contao_frontend');

        $requestWithRefInUrl = new Request();
        $requestWithRefInUrl->attributes->set('_route', 'contao_backend');
        $requestWithRefInUrl->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $requestWithRefInUrl->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');
        $requestWithRefInUrl->query->set('ref', 'dummyTestRefererId');

        $requestWithRefInUrlFrontend = clone $requestWithRefInUrl;
        $requestWithRefInUrlFrontend->attributes->set('_route', 'contao_frontend');

        return [
            'Test current referer null returns correct new referer for back end scope' => [
                ContaoCoreBundle::SCOPE_BACKEND,
                $request,
                null,
                [
                    'dummyTestRefererId' => [
                        'last' => '',
                        'current' => 'path/of/contao?having&query&string=1',
                    ],
                ],
            ],
            'Test referer returns correct new referer for back end scope' => [
                ContaoCoreBundle::SCOPE_BACKEND,
                $requestWithRefInUrl,
                [
                    'dummyTestRefererId' => [
                        'last' => '',
                        'current' => 'hi/I/am/your_current_referer.html',
                    ],
                ],
                [
                    'dummyTestRefererId' => [
                        'last' => 'hi/I/am/your_current_referer.html',
                        'current' => 'path/of/contao?having&query&string=1',
                    ],
                ],
            ],
            'Test current referer null returns null for front end scope' => [
                ContaoCoreBundle::SCOPE_FRONTEND,
                $requestFrontend,
                null,
                null,
            ],
            'Test referer returns correct new referer for front end scope' => [
                ContaoCoreBundle::SCOPE_FRONTEND,
                $requestWithRefInUrlFrontend,
                [
                    'last' => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                ],
                [
                    'last' => 'hi/I/am/your_current_referer.html',
                    'current' => 'path/of/contao?having&query&string=1',
                ],
            ],
            'Test referers are correctly added to the referers array (see #143)' => [
                ContaoCoreBundle::SCOPE_BACKEND,
                $requestWithRefInUrl,
                [
                    'dummyTestRefererId' => [
                        'last' => '',
                        'current' => 'hi/I/am/your_current_referer.html',
                    ],
                    'dummyTestRefererId1' => [
                        'last' => '',
                        'current' => 'hi/I/am/your_current_referer.html',
                    ],
                ],
                [
                    'dummyTestRefererId' => [
                        'last' => 'hi/I/am/your_current_referer.html',
                        'current' => 'path/of/contao?having&query&string=1',
                    ],
                    'dummyTestRefererId1' => [
                        'last' => '',
                        'current' => 'hi/I/am/your_current_referer.html',
                    ],
                ],
            ],
        ];
    }

    /**
     * Provides the data for the user-less tests.
     *
     * @return array
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
     * Returns the session listener object.
     *
     * @param SessionInterface      $session
     * @param TokenStorageInterface $tokenStorage
     *
     * @return StoreRefererListener
     */
    private function getListener(SessionInterface $session = null, TokenStorageInterface $tokenStorage = null)
    {
        if (null === $session) {
            $session = $this->mockSession();
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->getMock(
                'Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface'
            );
        }

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);

        return new StoreRefererListener($session, $tokenStorage, $trustResolver);
    }
}
