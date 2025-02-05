<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Matcher;

use Contao\CoreBundle\Routing\Matcher\PublishedFilter;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PublishedFilterTest extends TestCase
{
    public function testDoesNotFilterInPreviewEntryPoint(): void
    {
        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->never())
            ->method('all')
        ;

        $request = new Request();
        $request->attributes->set('_preview', true);

        $filter = new PublishedFilter();
        $filter->filter($collection, $request);
    }

    public function testSkipsRoutesWithoutPageModel(): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn(null)
        ;

        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn(['foo' => $route])
        ;

        $collection
            ->expects($this->never())
            ->method('remove')
        ;

        $filter = new PublishedFilter();
        $filter->filter($collection, new Request());
    }

    public function testRemovesARouteIfThePageHasNotBeenPublished(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->isPublic = false;
        $pageModel->rootIsPublic = true;

        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($pageModel)
        ;

        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn(['foo' => $route])
        ;

        $collection
            ->expects($this->once())
            ->method('remove')
            ->with('foo')
        ;

        $filter = new PublishedFilter();
        $filter->filter($collection, new Request());
    }

    public function testRemovesARouteIfTheRootPageHasNotBeenPublished(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->isPublic = true;
        $pageModel->rootIsPublic = false;

        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($pageModel)
        ;

        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn(['foo' => $route])
        ;

        $collection
            ->expects($this->once())
            ->method('remove')
            ->with('foo')
        ;

        $filter = new PublishedFilter();
        $filter->filter($collection, new Request());
    }

    public function testDoesNotRemoveARouteIfThePageHasBeenPublished(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->isPublic = true;
        $pageModel->rootIsPublic = true;

        $route = $this->createMock(Route::class);
        $route
            ->expects($this->once())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($pageModel)
        ;

        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn(['foo' => $route])
        ;

        $collection
            ->expects($this->never())
            ->method('remove')
        ;

        $filter = new PublishedFilter();
        $filter->filter($collection, new Request());
    }
}
