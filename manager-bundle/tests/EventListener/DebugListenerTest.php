<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener;

use Contao\ManagerBundle\EventListener\DebugListener;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class DebugListenerTest extends ContaoTestCase
{
    public function testReturnsRedirectResponseWithDebugEnabledCookie(): void
    {
        $listener = new DebugListener(
            $this->mockSecurityHelper(),
            $this->mockRequestStack(),
            $this->mockJwtManager(true, true)
        );

        $listener->onEnable();
    }

    public function testReturnsRedirectResponseWithDebugDisabledCookie(): void
    {
        $listener = new DebugListener(
            $this->mockSecurityHelper(),
            $this->mockRequestStack(),
            $this->mockJwtManager(true, false)
        );

        $listener->onDisable();
    }

    public function testResponseContainsReferer(): void
    {
        $listener = new DebugListener(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/foo/bar.html', 'foo=bar'),
            $this->mockJwtManager(true, true)
        );

        $response = $listener->onEnable();

        $this->assertSame('https://example.com/foo/bar.html?foo=bar', $response->getTargetUrl());
    }

    public function testThrowsAccessDeniedExceptionIfUserIsNotAdmin(): void
    {
        $listener = new DebugListener(
            $this->mockSecurityHelper(false),
            new RequestStack(),
            $this->mockJwtManager(false)
        );

        $this->expectException(AccessDeniedException::class);

        $listener->onEnable();
    }

    public function testThrowsExceptionIfRequestStackIsEmpty(): void
    {
        $listener = new DebugListener(
            $this->mockSecurityHelper(),
            new RequestStack(),
            $this->mockJwtManager(false)
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $listener->onEnable();
    }

    /**
     * @return Security&MockObject
     */
    private function mockSecurityHelper(bool $isAdmin = true): Security
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn($isAdmin)
        ;

        return $security;
    }

    /**
     * @return RequestStack&&MockObject
     */
    private function mockRequestStack(string $path = '', string $referer = null): RequestStack
    {
        $request = Request::create($path);

        if (null !== $referer) {
            $request->query->set('referer', base64_encode($referer));
        }

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        return $requestStack;
    }

    /**
     * @return JwtManager&&MockObject
     */
    private function mockJwtManager(bool $expectAddsCookie, bool $debug = null): JwtManager
    {
        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($expectAddsCookie ? $this->once() : $this->never())
            ->method('addResponseCookie')
            ->with($this->anything(), ['debug' => $debug])
        ;

        return $jwtManager;
    }
}
