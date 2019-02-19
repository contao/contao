<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\Logout;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\Logout\LogoutSuccessHandler;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\HttpUtils;

class LogoutSuccessHandlerTest extends TestCase
{
    public function testRedirectsToAGivenUrl(): void
    {
        $request = new Request();
        $request->query->set('redirect', 'http://localhost/home');

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $handler = new LogoutSuccessHandler($httpUtils, $scopeMatcher);

        /** @var RedirectResponse $response */
        $response = $handler->onLogoutSuccess($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTheRefererUrl(): void
    {
        $request = new Request();
        $request->headers->set('Referer', 'http://localhost/home');

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'http://localhost/home')
            ->willReturn(new RedirectResponse('http://localhost/home'))
        ;

        $handler = new LogoutSuccessHandler($httpUtils, $scopeMatcher);

        /** @var RedirectResponse $response */
        $response = $handler->onLogoutSuccess($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/home', $response->getTargetUrl());
    }

    public function testRedirectsToTheDefaultUrl(): void
    {
        $request = new Request();

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, '/')
            ->willReturn(new RedirectResponse('http://localhost'))
        ;

        $handler = new LogoutSuccessHandler($httpUtils, $scopeMatcher);

        /** @var RedirectResponse $response */
        $response = $handler->onLogoutSuccess($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost', $response->getTargetUrl());
    }

    public function testRedirectsToTheLoginOnBackendScope(): void
    {
        $request = new Request();

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'contao_backend_login')
            ->willReturn(new RedirectResponse('contao_backend_login'))
        ;

        $handler = new LogoutSuccessHandler($httpUtils, $scopeMatcher);

        /** @var RedirectResponse $response */
        $response = $handler->onLogoutSuccess($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('contao_backend_login', $response->getTargetUrl());
    }

    public function testClearsTheJwtCookieOnFrontendRequest(): void
    {
        $request = new Request();
        $response = new Response();

        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($this->once())
            ->method('clearResponseCookie')
            ->with($response)
        ;

        $request->attributes->set(JwtManager::ATTRIBUTE, $jwtManager);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);

        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(false)
        ;

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturn($response)
        ;

        $handler = new LogoutSuccessHandler($httpUtils, $scopeMatcher);
        $handler->onLogoutSuccess($request);
    }

    public function testClearsTheJwtCookieOnBackendRequest(): void
    {
        $request = new Request();
        $response = new Response();

        $jwtManager = $this->createMock(JwtManager::class);
        $jwtManager
            ->expects($this->once())
            ->method('clearResponseCookie')
            ->with($response)
        ;

        $request->attributes->set(JwtManager::ATTRIBUTE, $jwtManager);

        $scopeMatcher = $this->createMock(ScopeMatcher::class);

        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturn($response)
        ;

        $handler = new LogoutSuccessHandler($httpUtils, $scopeMatcher);
        $handler->onLogoutSuccess($request);
    }
}
