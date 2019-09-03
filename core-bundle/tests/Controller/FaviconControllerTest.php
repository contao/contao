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

use Contao\CoreBundle\Controller\FaviconController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FilesModel;
use Contao\PageModel;
use FOS\HttpCache\ResponseTagger;
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

        $controller = new FaviconController($framework, $this->createMock(ResponseTagger::class));
        $response = $controller($request);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testRobotsTxt(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->favicon = 'favicon-uuid';
        $pageModel->id = 42;

        $faviconModel = $this->mockClassWithProperties(FilesModel::class);
        $faviconModel->path = __DIR__.'/../Fixtures/images/favicon.ico';

        $pageModelAdapter = $this->mockAdapter(['findPublishedFallbackByHostname']);
        $pageModelAdapter
            ->expects($this->once())
            ->method('findPublishedFallbackByHostname')
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

        $responseTagger = $this->createMock(ResponseTagger::class);
        $responseTagger
            ->expects($this->once())
            ->method('addTags')
            ->with(['contao.db.tl_page.42'])
        ;

        $request = Request::create('/favicon.ico');

        $controller = new FaviconController($framework, $responseTagger);
        $response = $controller($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
