<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Authentication;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Authentication\AuthenticationEntryPoint;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\RouterInterface;

class AuthenticationEntryPointTest extends TestCase
{
    public function testThrowsExceptionForFrontendRequest(): void
    {
        $request = Request::create('http://localhost/login');

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $entryPoint = new AuthenticationEntryPoint(
            $this->createMock(RouterInterface::class),
            new UriSigner('secret'),
            $scopeMatcher
        );

        $this->expectException(UnauthorizedHttpException::class);

        $entryPoint->start($request);
    }

    public function testSignsTheBackendRedirectUrl(): void
    {
        $request = Request::create('http://localhost/contao/login?redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login', ['redirect' => $request->getUri()])
            ->willReturn('http://localhost/contao/login?redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html')
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $entryPoint = new AuthenticationEntryPoint(
            $router,
            new UriSigner('secret'),
            $scopeMatcher
        );

        $response = $entryPoint->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/contao/login?_hash=%2FxSCw6cwMlws5DEhBCvs0%2F75oQA8q%2FgMkZEnYCf6QSE%3D&redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html', $response->getTargetUrl());
    }

    public function testRedirectsAjaxRequests(): void
    {
        $request = Request::create('http://localhost/contao/login?redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login', ['redirect' => $request->getUri()])
            ->willReturn('http://localhost/contao/login?redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html')
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $entryPoint = new AuthenticationEntryPoint(
            $router,
            new UriSigner('secret'),
            $scopeMatcher
        );

        $response = $entryPoint->start($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->headers->has('Location'));
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('http://localhost/contao/login?_hash=%2FxSCw6cwMlws5DEhBCvs0%2F75oQA8q%2FgMkZEnYCf6QSE%3D&redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html', $response->headers->get('X-Ajax-Location'));
    }

    public function testAddsARefererToTheBackendRedirectUrlIfTheQueryIsEmpty(): void
    {
        $request = Request::create('/');

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login')
            ->willReturn('http://localhost/contao/login')
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->with($request)
            ->willReturn(true)
        ;

        $entryPoint = new AuthenticationEntryPoint(
            $router,
            new UriSigner('secret'),
            $scopeMatcher
        );

        $response = $entryPoint->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/contao/login?_hash=VmECH%2B5ZGFFG41uxiQVkwzSN%2F7YazPja98g5QNG4Zes%3D', $response->getTargetUrl());
    }
}
