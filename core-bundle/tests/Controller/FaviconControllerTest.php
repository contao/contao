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

use Contao\CoreBundle\Cache\EntityCacheTags;
use Contao\CoreBundle\Controller\FaviconController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FaviconControllerTest extends TestCase
{
    public function testNotFoundIfNoFaviconProvided(): void
    {
        $pageModelAdapter = $this->mockAdapter(['findPublishedFallbackByHostname']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedFallbackByHostname')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageModelAdapter]);
        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $request = Request::create('/robots.txt');
        $controller = new FaviconController($framework, $this->getFixturesDir(), $this->createMock(EntityCacheTags::class));
        $response = $controller($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testRegularFavicon(): void
    {
        $controller = $this->getController('images/favicon.ico');

        $request = Request::create('/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/x-icon', $response->headers->get('Content-Type'));
    }

    public function testIgnoresRequestPort(): void
    {
        $controller = $this->getController('images/favicon.ico');

        $request = Request::create('https://localhost:8000/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/x-icon', $response->headers->get('Content-Type'));
    }

    public function testSvgFavicon(): void
    {
        $controller = $this->getController('images/favicon.svg');

        $request = Request::create('/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
    }

    public function testPngFavicon(): void
    {
        $controller = $this->getController('images/favicon.png');

        $request = Request::create('/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    private function getController(string $iconPath): FaviconController
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->id = 42;
        $pageModel->favicon = 'favicon-uuid';

        $faviconModel = $this->mockClassWithProperties(FilesModel::class);
        $faviconModel->path = $iconPath;
        $faviconModel->extension = substr($iconPath, -3);

        $pageModelAdapter = $this->mockAdapter(['findPublishedFallbackByHostname']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedFallbackByHostname')
            ->with('localhost')
            ->willReturn($pageModel)
        ;

        $filesModelAdapter = $this->mockAdapter(['findByUuid']);
        $filesModelAdapter
            ->expects($this->once())
            ->method('findByUuid')
            ->with('favicon-uuid')
            ->willReturn($faviconModel)
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageModelAdapter,
            FilesModel::class => $filesModelAdapter,
        ]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $entityCacheTags = $this->createMock(EntityCacheTags::class);
        $entityCacheTags
            ->expects($this->once())
            ->method('tagWithModelInstance')
            ->with($pageModel)
        ;

        return new FaviconController($framework, $this->getFixturesDir(), $entityCacheTags);
    }
}
