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

use Contao\CoreBundle\Security\Authentication\AuthenticationEntryPoint;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\UriSigner;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationEntryPointTest extends TestCase
{
    public function testSignsTheRedirectUrl(): void
    {
        $request = Request::create('http://localhost/contao/login?redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html');

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturnCallback(
                static function (Request $request, string $url): RedirectResponse {
                    return new RedirectResponse($url);
                }
            )
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login', ['redirect' => $request->getUri()])
            ->willReturn('http://localhost/contao/login?redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html')
        ;

        $entryPoint = new AuthenticationEntryPoint($httpUtils, $router, new UriSigner('secret'));
        $response = $entryPoint->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/contao/login?_hash=%2FxSCw6cwMlws5DEhBCvs0%2F75oQA8q%2FgMkZEnYCf6QSE%3D&redirect=https%3A%2F%2Fcontao.org%2Fpreview.php%2Fabout-contao.html', $response->getTargetUrl());
    }

    public function testDoesNotAddARefererToTheRedirectUrlIfTheQueryIsEmpty(): void
    {
        $request = new Request();

        $httpUtils = $this->createMock(HttpUtils::class);
        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, 'contao_backend_login')
            ->willReturn(new RedirectResponse('http://localhost/contao/login'))
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $entryPoint = new AuthenticationEntryPoint($httpUtils, $router, new UriSigner('secret'));
        $response = $entryPoint->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/contao/login', $response->getTargetUrl());
    }
}
