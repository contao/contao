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
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
            new UriSigner('secret'),
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
            new UriSigner('secret'),
        );

        $listener->disableAction();
    }

    public function testResponseContainsReferer(): void
    {
        // The debug toggle is always requested via "/contao" (do=debug), while the
        // referer points back to the page the button was clicked on.
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/contao', '/foo/bar.html?foo=bar'),
            $this->mockJwtManager(true, true),
            $this->mockTokenManager(true),
            'contao_csrf_token',
            new UriSigner('secret'),
        );

        $response = $listener->enableAction();

        $this->assertSame('https://example.com/foo/bar.html?foo=bar', $response->getTargetUrl());
    }

    public function testResponseContainsRefererForPathBasedBackendRoute(): void
    {
        // Back end modules can be addressed via a path (e.g. "/contao/page") instead of
        // the "do" query parameter, in which case the module is not part of the
        // referer's query string.
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/contao', '/contao/page?act=edit&id=1'),
            $this->mockJwtManager(true, true),
            $this->mockTokenManager(true),
            'contao_csrf_token',
            new UriSigner('secret'),
        );

        $response = $listener->enableAction();

        $this->assertSame('https://example.com/contao/page?act=edit&id=1', $response->getTargetUrl());
    }

    public function testFallsBackToTheBackendRootIfThereIsNoReferer(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/contao'),
            $this->mockJwtManager(true, true),
            $this->mockTokenManager(true),
            'contao_csrf_token',
            new UriSigner('secret'),
        );

        $response = $listener->enableAction();

        $this->assertSame('https://example.com/contao', $response->getTargetUrl());
    }

    public function testIgnoresARefererThatIsNotASameOriginPath(): void
    {
        // A manipulated referer parameter must not be able to redirect to a foreign host
        // (e.g. a protocol-relative "//evil.example.com/" URL).
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/contao', '//evil.example.com/'),
            $this->mockJwtManager(true, true),
            $this->mockTokenManager(true),
            'contao_csrf_token',
            new UriSigner('secret'),
        );

        $response = $listener->enableAction();

        $this->assertSame('https://example.com/contao', $response->getTargetUrl());
    }

    public function testFailsIfTheUriSignatureIsMissing(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(),
            $this->mockRequestStack('https://example.com/contao', '/contao/page', false),
            $this->mockJwtManager(false),
            $this->mockTokenManager(true),
            'contao_csrf_token',
            new UriSigner('secret'),
        );

        $this->expectException(BadRequestHttpException::class);

        $listener->enableAction();
    }

    public function testThrowsAccessDeniedExceptionIfUserIsNotAdmin(): void
    {
        $listener = new DebugController(
            $this->mockSecurityHelper(false),
            new RequestStack(),
            $this->mockJwtManager(false),
            $this->mockTokenManager(false),
            'contao_csrf_token',
            new UriSigner('secret'),
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
            new UriSigner('secret'),
        );

        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('The request stack did not contain a request');

        $listener->enableAction();
    }

    private function mockSecurityHelper(bool $isAdmin = true): Security&MockObject
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->willReturn($isAdmin)
        ;

        return $security;
    }

    private function mockRequestStack(string $path = 'https://example.com/contao', string|null $referer = null, bool $signed = true): RequestStack&MockObject
    {
        $uriSigner = new UriSigner('secret');

        if (null !== $referer) {
            $path .= '?referer='.rawurlencode(base64_encode($referer));
        }

        $request = Request::create($signed ? $uriSigner->sign($path) : $path);

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
