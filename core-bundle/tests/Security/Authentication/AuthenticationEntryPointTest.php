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

use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\ResponseException;
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
    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_PTY']);
    }

    public function testGeneratesThe401PageInTheFrontend()
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
            $this->mockContaoFramework(),
            $scopeMatcher
        );

        PageError401::$exception = null;
        $GLOBALS['TL_PTY']['error_401'] = PageError401::class;

        $response = $entryPoint->start($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('foo', $response->getContent());
    }

    public function testReturnsResponseFromResponseException()
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
            $this->mockContaoFramework(),
            $scopeMatcher
        );

        PageError401::$exception = new ResponseException(new Response('bar'));
        $GLOBALS['TL_PTY']['error_401'] = PageError401::class;

        $response = $entryPoint->start($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('bar', $response->getContent());
    }

    public function testThrowsExceptionIfError401PageIsNotSet()
    {
        $this->expectException(UnauthorizedHttpException::class);

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
            $this->mockContaoFramework(),
            $scopeMatcher
        );

        $entryPoint->start($request);
    }

    public function testThrowsExceptionIfError401PageClassDoesNotExit()
    {
        $this->expectException(UnauthorizedHttpException::class);

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
            $this->mockContaoFramework(),
            $scopeMatcher
        );

        $GLOBALS['TL_PTY']['error_401'] = 'Foo\Bar';

        $entryPoint->start($request);
    }

    public function testConvertsInsufficientAuthenticationException()
    {
        $this->expectException(UnauthorizedHttpException::class);

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
            $this->mockContaoFramework(),
            $scopeMatcher
        );

        PageError401::$exception = new InsufficientAuthenticationException();
        $GLOBALS['TL_PTY']['error_401'] = PageError401::class;

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
            $this->mockContaoFramework(),
            $scopeMatcher
        );

        $response = $entryPoint->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/contao/login?_hash=%2FxSCw6cwMlws5DEhBCvs0%2F75oQA8q%2FgMkZEnYCf6QSE%3D&redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html', $response->getTargetUrl());
    }

    public function testDoesNotAddARefererToTheBackendRedirectUrlIfTheQueryIsEmpty(): void
    {
        $request = new Request();

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
            $this->mockContaoFramework(),
            $scopeMatcher
        );

        $response = $entryPoint->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/contao/login', $response->getTargetUrl());
    }
}

class PageError401
{
    public static $exception;

    public function getResponse()
    {
        if (self::$exception) {
            throw self::$exception;
        }

        return new Response('foo');
    }
}

