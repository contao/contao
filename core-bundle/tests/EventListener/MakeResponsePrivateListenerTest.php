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

use Contao\CoreBundle\EventListener\MakeResponsePrivateListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MakeResponsePrivateListenerTest extends TestCase
{
    public function testIgnoresSubRequests(): void
    {
        // Public response with cookie, should be turned into a private response if it was a master request
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->headers->setCookie(Cookie::create('foobar', 'foobar'));

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener();
        $listener->onKernelResponse($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
    }

    public function testIgnoresRequestsThatMatchNoCondition(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener();
        $listener->onKernelResponse($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
        $this->assertSame('600', $response->headers->getCacheControlDirective('max-age'));
    }

    public function testMakesResponsePrivateWhenTheSessionWasStarted(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $request = new Request();
        $request->setSession($session);

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener();
        $listener->onKernelResponse($event);

        $this->assertTrue($response->headers->getCacheControlDirective('private'));
    }

    public function testMakesResponsePrivateWhenTheResponseContainsACookie(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->headers->setCookie(Cookie::create('foobar', 'foobar'));

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener();
        $listener->onKernelResponse($event);

        $this->assertTrue($response->headers->getCacheControlDirective('private'));
    }

    public function testMakesResponsePrivateWhenItContainsVaryCookieAndTheRequestProvidesAtLeastOne(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->setVary('Cookie');

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request([], [], [], ['super-cookie' => 'value']),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener();
        $listener->onKernelResponse($event);

        $this->assertTrue($response->headers->getCacheControlDirective('private'));
    }

    public function testIgnoresTheResponseWhenItContainsVaryCookieButTheRequestDoesNotSendAnyCookie(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->setVary('Cookie');

        $event = new FilterResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener();
        $listener->onKernelResponse($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
    }
}
