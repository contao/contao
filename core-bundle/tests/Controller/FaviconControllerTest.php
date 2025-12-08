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

use Contao\CoreBundle\Cache\CacheTagManager;
use Contao\CoreBundle\Controller\FaviconController;
use Contao\CoreBundle\Routing\PageFinder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FaviconControllerTest extends TestCase
{
    public function testThrowsNotFoundHttpExceptionIfNoRootPageFound(): void
    {
        $request = Request::create('https://www.example.org/favicon.ico');

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPageForHostAndLanguage')
            ->with('www.example.org')
            ->willReturn(null)
        ;

        $framework = $this->createContaoFrameworkStub();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $this->expectException(NotFoundHttpException::class);

        $controller = new FaviconController($framework, $pageFinder, $this->getFixturesDir(), $this->createMock(CacheTagManager::class));
        $controller($request);
    }

    public function testThrowsNotFoundHttpExceptionIfNoFaviconProvided(): void
    {
        $request = Request::create('https://www.example.org/favicon.ico');
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class, ['id' => 42, 'favicon' => null]);

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPageForHostAndLanguage')
            ->with('www.example.org')
            ->willReturn($pageModel)
        ;

        $framework = $this->createContaoFrameworkStub();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $this->expectException(NotFoundHttpException::class);

        $controller = new FaviconController($framework, $pageFinder, $this->getFixturesDir(), $this->createMock(CacheTagManager::class));
        $controller($request);
    }

    public function testRegularFavicon(): void
    {
        $controller = $this->getController('images/favicon.ico');

        $request = Request::create('https://www.example.org/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/x-icon', $response->headers->get('Content-Type'));
    }

    public function testIgnoresRequestPort(): void
    {
        $controller = $this->getController('images/favicon.ico');

        $request = Request::create('https://www.example.org:8000/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/x-icon', $response->headers->get('Content-Type'));
    }

    public function testSvgFavicon(): void
    {
        $controller = $this->getController('images/favicon.svg');

        $request = Request::create('https://www.example.org/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
    }

    public function testPngFavicon(): void
    {
        $controller = $this->getController('images/favicon.png');

        $request = Request::create('https://www.example.org/favicon.ico');
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('image/png', $response->headers->get('Content-Type'));
    }

    private function getController(string $iconPath): FaviconController
    {
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class);
        $pageModel->id = 42;
        $pageModel->favicon = 'favicon-uuid';

        $faviconModel = $this->createClassWithPropertiesStub(FilesModel::class);
        $faviconModel->path = $iconPath;
        $faviconModel->extension = substr($iconPath, -3);

        $filesModelAdapter = $this->createAdapterStub(['findByUuid']);
        $filesModelAdapter
            ->expects($this->once())
            ->method('findByUuid')
            ->with('favicon-uuid')
            ->willReturn($faviconModel)
        ;

        $framework = $this->createContaoFrameworkStub([
            FilesModel::class => $filesModelAdapter,
        ]);

        $framework
            ->expects($this->once())
            ->method('initialize')
        ;

        $pageFinder = $this->createMock(PageFinder::class);
        $pageFinder
            ->expects($this->once())
            ->method('findRootPageForHostAndLanguage')
            ->with('www.example.org')
            ->willReturn($pageModel)
        ;

        $cacheTags = $this->createMock(CacheTagManager::class);
        $cacheTags
            ->expects($this->once())
            ->method('tagWithModelInstance')
            ->with($pageModel)
        ;

        return new FaviconController($framework, $pageFinder, $this->getFixturesDir(), $cacheTags);
    }
}
