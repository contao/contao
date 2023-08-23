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
    public function testStoresTheReferer(Request $request, array|null $currentReferer, array|null $expectedReferer): void
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
        $request->attributes->set('_contao_referer_id', 'newRefererId');
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $request->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');

        $requestWithRefInUrl = new Request();
        $requestWithRefInUrl->query->set('ref', 'existingRefererId');

        $requestWithRefInUrl->attributes->set('_route', 'contao_backend');
        $requestWithRefInUrl->attributes->set('_contao_referer_id', 'newRefererId');
        $requestWithRefInUrl->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $requestWithRefInUrl->server->set('REQUEST_URI', '/path/of/contao?having&query&string=1');

        yield 'Test current referer null returns correct new referer' => [
            $request,
            null,
            [
                'newRefererId' => [
                    'last' => '',
                    'current' => '/path/of/contao?having&query&string=1',
                ],
            ],
        ];

        yield 'Test "last" remains untouched if there is no existing refer ID in the URL' => [
            $requestWithRefInUrl,
            [
                'newRefererId' => [
                    'last' => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                ],
            ],
            [
                'newRefererId' => [
                    'last' => '',
                    'current' => '/path/of/contao?having&query&string=1',
                ],
            ],
        ];

        yield 'Test referers are correctly added to the referers array (see #143)' => [
            $requestWithRefInUrl,
            [
                'existingRefererId' => [
                    'last' => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                ],
                'newRefererId' => [
                    'last' => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                ],
            ],
            [
                'existingRefererId' => [
                    'last' => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                ],
                'newRefererId' => [
                    'last' => 'hi/I/am/your_current_referer.html',
                    'current' => '/path/of/contao?having&query&string=1',
                ],
            ],
        ];

        yield 'Test referers are correctly replaced if already present (see #2722)' => [
            $requestWithRefInUrl,
            [
                'existingRefererId' => [
                    'last' => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                    'tl_foobar' => 'contao?do=foobar&table=tl_foobar&id=1',
                ],
                'newRefererId' => [
                    'tl_foobar' => 'contao?do=foobar&table=tl_foobar&id=2',
                ],
            ],
            [
                'existingRefererId' => [
                    'last' => '',
                    'current' => 'hi/I/am/your_current_referer.html',
                    'tl_foobar' => 'contao?do=foobar&table=tl_foobar&id=1',
                ],
                'newRefererId' => [
                    'last' => 'hi/I/am/your_current_referer.html',
                    'current' => '/path/of/contao?having&query&string=1',
                    'tl_foobar' => 'contao?do=foobar&table=tl_foobar&id=2',
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
            new Response('', 404),
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
            new Response('', 404),
        );

        $listener = $this->getListener();
        $listener($responseEvent);
    }

    /**
     * @dataProvider noContaoUserProvider
     */
    public function testDoesNotStoreTheRefererIfThereIsNoContaoUser(UserInterface|null $user = null): void
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

    private function getListener(UserInterface|null $user = null, bool $expectsSecurityCall = false): StoreRefererListener
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($expectsSecurityCall || $user ? $this->once() : $this->never())
            ->method('getUser')
            ->willReturn($user)
        ;

        return new StoreRefererListener($security, $this->mockScopeMatcher());
    }

    private function getResponseEvent(Request|null $request = null): ResponseEvent
    {
        $kernel = $this->createMock(KernelInterface::class);

        return new ResponseEvent($kernel, $request ?? new Request(), HttpKernelInterface::MAIN_REQUEST, new Response());
    }
}
