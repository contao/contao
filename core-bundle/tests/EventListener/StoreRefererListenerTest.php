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
use Contao\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class StoreRefererListenerTest extends TestCase
{
    /**
     * @dataProvider refererStoredOnKernelResponseProvider
     */
    public function testStoresTheReferer(Request $request, ?array $currentReferer, ?array $expectedReferer): void
    {
        // Set the current referer URLs
        $session = $this->mockSession();
        $session->set('referer', $currentReferer);

        $request->setSession($session);

        $listener = $this->getListener($this->createMock(User::class));
        $listener($this->getResponseEvent($request));

        $this->assertSame($expectedReferer, $session->get('referer'));
    }

    public function refererStoredOnKernelResponseProvider(): \Generator
    {
        $request = new Request();
        $request->attributes->set('_route', 'contao_backend');
        $request->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);
        $request->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');

        $requestWithRefInUrl = new Request();
        $requestWithRefInUrl->attributes->set('_route', 'contao_backend');
        $requestWithRefInUrl->attributes->set('_contao_referer_id', 'dummyTestRefererId');
        $requestWithRefInUrl->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);
        $requestWithRefInUrl->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');
        $requestWithRefInUrl->query->set('ref', 'dummyTestRefererId');

        yield 'Test current referer null returns correct new referer' => [
            $request,
            null,
            [
                'dummyTestRefererId' => [
                    'last' => '',
                    'current' => 'path/of/contao?having&query&string=1',
                ],
            ],
        ];

        yield 'Test referer returns correct new referer' => [
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

        $responseEvent = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 404)
        );

        $listener = $this->getListener();
        $listener($responseEvent);
    }

    public function testDoesNotStoreTheRefererIfTheResponseStatusIsNot200(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $responseEvent = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response('', 404)
        );

        $listener = $this->getListener();
        $listener($responseEvent);
    }

    /**
     * @dataProvider noContaoUserProvider
     */
    public function testDoesNotStoreTheRefererIfThereIsNoContaoUser(UserInterface $user = null): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('set')
        ;

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->getListener($user, true);
        $listener($this->getResponseEvent($request));
    }

    public function noContaoUserProvider(): \Generator
    {
        yield [null];
        yield [$this->createMock(UserInterface::class)];
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

        $listener = $this->getListener();
        $listener($this->getResponseEvent($request));
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
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, new Response());

        $listener = $this->getListener();
        $listener($event);
    }

    public function testDoesNotStoreTheRefererIfTheBackEndSessionCannotBeModified(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->never())
            ->method('set')
        ;

        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->getListener($this->createMock(User::class));
        $listener($this->getResponseEvent($request));
    }

    public function testFailsToStoreTheRefererIfThereIsNoSession(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'contao_backend');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $listener = $this->getListener($this->createMock(User::class));

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request did not contain a session.');

        $listener($this->getResponseEvent($request));
    }

    private function getListener(UserInterface $user = null, $expectsSecurityCall = false): StoreRefererListener
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($expectsSecurityCall || null !== $user ? $this->once() : $this->never())
            ->method('getUser')
            ->willReturn($user)
        ;

        return new StoreRefererListener($security, $this->mockScopeMatcher());
    }

    private function getResponseEvent(Request $request = null): ResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        if (null === $request) {
            $request = new Request();
        }

        return new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, new Response());
    }
}
