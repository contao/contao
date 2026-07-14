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
        $response = new RedirectResponse('https://example.com');
        $response->headers = new ResponseHeaderBag();

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['useSSL' => false]);

        $request = Request::create('https://localhost/');
        $request->attributes->set('pageModel', $pageModel);

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');

        $this->assertSame('max-age=0', $response->headers->get('Strict-Transport-Security'));
    }

    public function testDoesNotAddTheHeaderForInsecureRequess(): void
    {
        $response = new RedirectResponse('http://example.com');
        $response->headers = new ResponseHeaderBag();

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['useSSL' => false]);

        $request = Request::create('http://localhost/');
        $request->attributes->set('pageModel', $pageModel);

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function testDoesNotAddTheHeaderWithoutPageModel(): void
    {
        $response = new RedirectResponse('https://example.com');
        $response->headers = new ResponseHeaderBag();

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $request = Request::create('https://localhost/');

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function testDoesNotAddTheHeaderIfRootPageUsesSSL(): void
    {
        $response = new RedirectResponse('https://example.com');
        $response->headers = new ResponseHeaderBag();

        $inner = $this->createMock(SymfonyRedirectController::class);
        $inner
            ->expects($this->once())
            ->method('urlRedirectAction')
            ->willReturn($response)
        ;

        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['useSSL' => true]);

        $request = Request::create('https://localhost/');
        $request->attributes->set('pageModel', $pageModel);

        $controller = new RedirectController($inner);
        $controller->urlRedirectAction($request, '/foo/bar');

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }
}
