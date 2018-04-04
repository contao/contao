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
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationEntryPointTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $entryPoint = new AuthenticationEntryPoint(
            $this->createMock(HttpUtils::class),
            $this->createMock(RouterInterface::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Security\Authentication\AuthenticationEntryPoint', $entryPoint);
    }

    public function testAddsTheRefererToTheRedirectUrl(): void
    {
        $request = new Request();
        $request->server->set('QUERY_STRING', 'do=page');
        $request->query->add(['do' => 'page']);

        $httpUtils = $this->createMock(HttpUtils::class);

        $httpUtils
            ->expects($this->once())
            ->method('createRedirectResponse')
            ->willReturnCallback(
                function (Request $request, string $url): RedirectResponse {
                    return new RedirectResponse($url);
                }
            )
        ;

        $url = 'http://localhost/contao/login?referer='.base64_encode('do=page');

        $router = $this->createMock(RouterInterface::class);

        $router
            ->expects($this->once())
            ->method('generate')
            ->with('contao_backend_login', ['referer' => base64_encode('do=page')])
            ->willReturn($url)
        ;

        $entryPoint = new AuthenticationEntryPoint($httpUtils, $router);
        $response = $entryPoint->start($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame($url, $response->getTargetUrl());
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

        $entryPoint = new AuthenticationEntryPoint($httpUtils, $router);
        $response = $entryPoint->start($request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertSame('http://localhost/contao/login', $response->getTargetUrl());
    }
}
