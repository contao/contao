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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\StoreRefererListener;
use Contao\CoreBundle\Security\Authentication\ContaoToken;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class StoreRefererListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = $this->mockListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\StoreRefererListener', $listener);
    }

    /**
     * @param Request    $request
     * @param array|null $currentReferer
     * @param array|null $expectedReferer
     *
     * @dataProvider refererStoredOnKernelResponseProvider
     */
    public function testStoresTheReferer(Request $request, ?array $currentReferer, ?array $expectedReferer): void
    {
        $responseEvent = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($this->createMock(ContaoToken::class))
        ;

        // Set the current referer URLs
        $session = $this->mockSession();
        $session->set('referer', $currentReferer);

        $listener = $this->mockListener($session, $tokenStorage);
        $listener->onKernelResponse($responseEvent);

        $this->assertSame($expectedReferer, $session->get('referer'));
    }

    /**
     * @return array
     */
    public function refererStoredOnKernelResponseProvider()
    {
        $request = new Request();
        $request->attributes->set('_route', 'contao_backend');
        $request->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);
        $request->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');

        $requestFrontend = clone $request;
        $requestFrontend->attributes->set('_route', 'contao_frontend');
        $requestFrontend->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $requestWithRefInUrl = new Request();
        $requestWithRefInUrl->attributes->set('_route', 'contao_backend');
        $requestWithRefInUrl->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $requestWithRefInUrl->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);
        $requestWithRefInUrl->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');
        $requestWithRefInUrl->query->set('ref', 'dummyTestRefererId');

        $requestWithRefInUrlFrontend = clone $requestWithRefInUrl;
        $requestWithRefInUrlFrontend->attributes->set('_route', 'contao_frontend');
        $requestWithRefInUrlFrontend->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        return [
            'Test current referer null returns correct new referer for back end scope' => [
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
                $requestFrontend,
                null,
                null,
            ],
            'Test referer returns correct new referer for front end scope' => [
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
     * @param AnonymousToken $noUserReturn
     *
     * @dataProvider noUserProvider
     */
    public function testDoesNotStoreTheRefererIfThereIsNoUser(AnonymousToken $noUserReturn = null): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('set')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($noUserReturn)
        ;

        $listener = $this->mockListener($session, $tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * @return array
     */
    public function noUserProvider(): array
    {
        $anonymousToken = new AnonymousToken('key', 'anon.');

        return [
            [null],
            [$anonymousToken],
        ];
    }

    public function testDoesNotStoreTheRefererUponSubrequests(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST,
            new Response()
        );

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('set')
        ;

        $listener = $this->mockListener($session);
        $listener->onKernelResponse($responseEvent);
    }

    public function testDoesNotStoreTheRefererIfTheBackEndSessionCannotBeModified(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        $session = $this->createMock(SessionInterface::class);

        $session
            ->expects($this->never())
            ->method('set')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($this->createMock(ContaoToken::class))
        ;

        $listener = $this->mockListener($session, $tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * Mocks a session listener.
     *
     * @param SessionInterface      $session
     * @param TokenStorageInterface $tokenStorage
     *
     * @return StoreRefererListener
     */
    private function mockListener(SessionInterface $session = null, TokenStorageInterface $tokenStorage = null): StoreRefererListener
    {
        if (null === $session) {
            $session = $this->mockSession();
        }

        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);

        return new StoreRefererListener($session, $tokenStorage, $trustResolver, $this->mockScopeMatcher());
    }
}
