<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\RedirectController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Bundle\FrameworkBundle\Controller\RedirectController as SymfonyRedirectController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class RedirectControllerTest extends TestCase
{
    public function testAddsTheHeader(): void
    {
        $response = $this->createMock(RedirectResponse::class);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers
            ->expects($this->once())
            ->method('set')
            ->with('Strict-Transport-Security', 'max-age=0')
        ;

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $pageModel = $this->mockClassWithProperties(PageModel::class, ['useSSL' => false]);
        $request = Request::create('https://localhost/');
        $request->attributes->set('pageModel', $pageModel);

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');
    }

    public function testDoesNotAddTheHeaderForInsecureRequess(): void
    {
        $response = $this->createMock(RedirectResponse::class);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers
            ->expects($this->never())
            ->method('set')
        ;

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $pageModel = $this->mockClassWithProperties(PageModel::class, ['useSSL' => false]);
        $request = Request::create('http://localhost/');
        $request->attributes->set('pageModel', $pageModel);

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');
    }

    public function testDoesNotAddTheHeaderWithoutPageModel(): void
    {
        $response = $this->createMock(RedirectResponse::class);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers
            ->expects($this->never())
            ->method('set')
        ;

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $request = Request::create('https://localhost/');

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');
    }

    public function testDoesNotAddTheHeaderIfRootPageUsesSSL(): void
    {
        $response = $this->createMock(RedirectResponse::class);
        $response->headers = $this->createMock(ResponseHeaderBag::class);
        $response->headers
            ->expects($this->never())
            ->method('set')
        ;

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $pageModel = $this->mockClassWithProperties(PageModel::class, ['useSSL' => true]);
        $request = Request::create('https://localhost/');
        $request->attributes->set('pageModel', $pageModel);

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');
    }
}
