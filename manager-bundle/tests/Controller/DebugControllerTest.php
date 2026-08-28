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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\ManagerBundle\Controller\DebugController;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class DebugControllerTest extends ContaoTestCase
{
    public function testReturnsRedirectResponseWithDebugEnabledCookie(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack(),
            $this->mockJwtManager(true, true),
            $this->mockTokenManager(true),
            'contao_csrf_token',
        );

        $listener->enableAction();
    }

    public function testReturnsRedirectResponseWithDebugDisabledCookie(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack(),
            $this->mockJwtManager(true, false),
            $this->mockTokenManager(true),
            'contao_csrf_token',
        );

        $listener->disableAction();
    }

    public function testResponseContainsReferer(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/foo/bar.html', 'foo=bar'),
            $this->mockJwtManager(true, true),
            $this->mockTokenManager(true),
            'contao_csrf_token',
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
            $this->mockTokenManager(false),
            'contao_csrf_token',
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
            $this->mockTokenManager(false),
            'contao_csrf_token',
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $listener->enableAction();
    }

    private function mockSecurityHelper(bool $isAdmin = true): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn($isAdmin)
        ;

        return $security;
    }

    private function mockRequestStack(string $path = '', string|null $referer = null): MockObject&RequestStack
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

    private function mockJwtManager(bool $expectAddsCookie, bool|null $debug = null): JwtManager&MockObject
    {
        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($expectAddsCookie ? $this->once() : $this->never())
            ->method('addResponseCookie')
            ->with($this->anything(), ['debug' => $debug])
        ;

        return $jwtManager;
    }

    private function mockTokenManager(bool $expectsToBeValidated): ContaoCsrfTokenManager&MockObject
    {
        $tokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $tokenManager
            ->expects($expectsToBeValidated ? $this->once() : $this->never())
            ->method('isTokenValid')
            ->willReturn(true)
        ;

        return $tokenManager;
    }
}
