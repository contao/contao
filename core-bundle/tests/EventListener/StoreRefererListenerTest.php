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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\StoreRefererListener;
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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class StoreRefererListenerTest extends TestCase
{
    /**
     * @dataProvider refererStoredOnKernelResponseProvider
     */
    public function testStoresTheReferer(Request $request, ?array $currentReferer, ?array $expectedReferer): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($this->createMock(UsernamePasswordToken::class))
        ;

        // Set the current referer URLs
        $session = $this->mockSession();
        $session->set('referer', $currentReferer);

        $request->setSession($session);

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelResponse($this->mockResponseEvent($request));

        $this->assertSame($expectedReferer, $session->get('referer'));
    }

    public function refererStoredOnKernelResponseProvider(): \Generator
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

        yield 'Test current referer null returns correct new referer for back end scope' => [
            $request,
            null,
            [
                'dummyTestRefererId' => [
                    'last' => '',
                    'current' => 'path/of/contao?having&query&string=1',
                ],
            ],
        ];

        yield 'Test referer returns correct new referer for back end scope' => [
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
        ];

        yield 'Test current referer null returns null for front end scope' => [
            $requestFrontend,
            null,
            null,
        ];

        yield 'Test referer returns correct new referer for front end scope' => [
            $requestWithRefInUrlFrontend,
            [
                'last' => '',
                'current' => 'hi/I/am/your_current_referer.html',
            ],
            [
                'last' => 'hi/I/am/your_current_referer.html',
                'current' => 'path/of/contao?having&query&string=1',
            ],
        ];

        yield 'Test referers are correctly added to the referers array (see #143)' => [
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
        ];
    }

    public function testDoesNotStoreTheRefererIfTheRequestMethodIsNotGet(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);
        $request->setMethod(Request::METHOD_POST);

        $responseEvent = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response('', 404)
        );

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getToken')
        ;

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    public function testDoesNotStoreTheRefererIfTheResponseStatusIsNot200(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            new Response('', 404)
        );

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->never())
            ->method('getToken')
        ;

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelResponse($responseEvent);
    }

    /**
     * @dataProvider noUserProvider
     */
    public function testDoesNotStoreTheRefererIfThereIsNoUser(AnonymousToken $noUserReturn = null): void
    {
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

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelResponse($this->mockResponseEvent($request));
    }

    public function noUserProvider(): \Generator
    {
        yield [null];
        yield [new AnonymousToken('key', 'anon.')];
    }

    public function testDoesNotStoreTheRefererIfNotAContaoRequest(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('set')
        ;

        $request = new Request();
        $request->setSession($session);

        $listener = $this->mockListener();
        $listener->onKernelResponse($this->mockResponseEvent($request));
    }

    public function testDoesNotStoreTheRefererUponSubrequests(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('set')
        ;

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, new Response());

        $listener = $this->mockListener();
        $listener->onKernelResponse($event);
    }

    public function testDoesNotStoreTheRefererIfTheBackEndSessionCannotBeModified(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('set')
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($this->createMock(UsernamePasswordToken::class))
        ;

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->mockListener($tokenStorage);
        $listener->onKernelResponse($this->mockResponseEvent($request));
    }

    /**
     * @dataProvider noSessionProvider
     */
    public function testFailsToStoreTheRefererIfThereIsNoSession(string $scope): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock(UsernamePasswordToken::class))
        ;

        $request = new Request();
        $request->attributes->set('_route', 'contao_backend');
        $request->attributes->set('_scope', $scope);

        $listener = $this->mockListener($tokenStorage);

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request did not contain a session.');

        $listener->onKernelResponse($this->mockResponseEvent($request));
    }

    public function noSessionProvider(): \Generator
    {
        yield [ContaoCoreBundle::SCOPE_BACKEND];
        yield [ContaoCoreBundle::SCOPE_FRONTEND];
    }

    private function mockListener(TokenStorageInterface $tokenStorage = null): StoreRefererListener
    {
        if (null === $tokenStorage) {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
        }

        $trustResolver = new AuthenticationTrustResolver(AnonymousToken::class, RememberMeToken::class);

        return new StoreRefererListener($tokenStorage, $trustResolver, $this->mockScopeMatcher());
    }

    private function mockResponseEvent(Request $request = null): FilterResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        return new FilterResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, new Response());
    }
}
