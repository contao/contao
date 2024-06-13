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
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class MakeResponsePrivateListenerTest extends TestCase
{
    public function testIgnoresNonContaoMainRequests(): void
    {
        // Public response with cookie, should be turned into a private response if it was a main request
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->headers->setCookie(Cookie::create('foobar', 'foobar'));

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(false));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
        $this->assertFalse($response->headers->has(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testIgnoresRequestsThatMatchNoCondition(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
        $this->assertSame('600', $response->headers->getCacheControlDirective('max-age'));
        $this->assertFalse($response->headers->has(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testMakesResponsePrivateWhenAnAuthorizationHeaderIsPresent(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $request = new Request();
        $request->headers->set('Authorization', 'secret-token');

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('private'));
        $this->assertSame('authorization', $response->headers->get(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testIgnoresTheResponseWhenAnAuthorizationHeaderIsEmpty(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $request = new Request();
        $request->headers->set('Authorization', '');

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
        $this->assertFalse($response->headers->has(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testIgnoresTheResponseWhenAnAuthorizationHeaderIsNull(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $request = new Request();
        $request->headers->set('Authorization', null);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
        $this->assertFalse($response->headers->has(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testIgnoresTheResponseWhenTheSessionWasStartedButIsEmpty(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('foobar', 'foobaz'); // This starts the session
        $session->remove('foobar'); // This removes the value but the session remains started

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $request = new Request();
        $request->setSession($session);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
        $this->assertFalse($response->headers->has(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testMakesResponsePrivateWhenTheSessionIsStartedAndNotEmpty(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set('foobar', 'foobaz');

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);

        $request = new Request();
        $request->setSession($session);
        $request->cookies->set($session->getName(), $session->getId());

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('private'));
        $this->assertSame('session-cookie', $response->headers->get(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testMakesResponsePrivateWhenTheResponseContainsACookie(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->headers->setCookie(Cookie::create('foobar', 'foobar'));
        $response->headers->setCookie(Cookie::create('foobar2', 'foobar'));

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('private'));
        $this->assertSame('response-cookies (foobar, foobar2)', $response->headers->get(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testMakesResponsePrivateWhenItContainsVaryCookieAndTheRequestProvidesAtLeastOne(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->setVary('Cookie');

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request([], [], [], ['super-cookie' => 'value']),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('private'));
        $this->assertSame('request-cookies (super-cookie)', $response->headers->get(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testIgnoresTheResponseWhenItContainsVaryCookieButTheRequestDoesNotSendAnyCookie(): void
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge(600);
        $response->setVary('Cookie');

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->makeResponsePrivate($event);

        $this->assertTrue($response->headers->getCacheControlDirective('public'));
        $this->assertFalse($response->headers->has(MakeResponsePrivateListener::DEBUG_HEADER));
    }

    public function testDisablesSymfonyAutoCache(): void
    {
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(true));
        $listener->disableSymfonyAutoCacheControl($event);

        $this->assertTrue($response->headers->has(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER));
    }

    public function testDoesNotDisableSymfonyAutoCache(): void
    {
        $response = new Response();

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $listener = new MakeResponsePrivateListener($this->createScopeMatcher(false));
        $listener->disableSymfonyAutoCacheControl($event);

        $this->assertFalse($response->headers->has(AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER));
    }

    private function createScopeMatcher(bool $isContaoMainRequest): ScopeMatcher
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isContaoMainRequest')
            ->willReturn($isContaoMainRequest)
        ;

        return $scopeMatcher;
    }
}
