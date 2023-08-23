<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Controller;

use Contao\ManagerBundle\Controller\DebugController;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class DebugControllerTest extends ContaoTestCase
{
    public function testReturnsRedirectResponseWithDebugEnabledCookie(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack(),
            $this->mockJwtManager(true, true),
        );

        $listener->enableAction();
    }

    public function testReturnsRedirectResponseWithDebugDisabledCookie(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack(),
            $this->mockJwtManager(true, false),
        );

        $listener->disableAction();
    }

    public function testResponseContainsReferer(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/foo/bar.html', 'foo=bar'),
            $this->mockJwtManager(true, true),
        );

        $response = $listener->enableAction();

        $this->assertSame('https://example.com/foo/bar.html?foo=bar', $response->getTargetUrl());
    }

    public function testThrowsAccessDeniedExceptionIfUserIsNotAdmin(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(false),
            new RequestStack(),
            $this->mockJwtManager(false),
        );

        $this->expectException(AccessDeniedException::class);

        $listener->enableAction();
    }

    public function testThrowsExceptionIfRequestStackIsEmpty(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            new RequestStack(),
            $this->mockJwtManager(false),
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $listener->enableAction();
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
     * @return RequestStack&MockObject
     */
    private function mockRequestStack(string $path = '', string|null $referer = null): RequestStack
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
     * @return JwtManager&MockObject
     */
    private function mockJwtManager(bool $expectAddsCookie, bool|null $debug = null): JwtManager
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
