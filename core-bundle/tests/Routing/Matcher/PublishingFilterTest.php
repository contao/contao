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

use Contao\CoreBundle\Routing\Matcher\PublishingFilter;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PublishingFilterTest extends TestCase
{
    public function testDoesNotFilterInPreviewMode()
    {
        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->never())
            ->method('all')
        ;

        $filter = new PublishingFilter($this->mockTokenChecker(true));
        $filter->filter($collection, $this->createMock(Request::class));
    }

    public function testSkipsRoutesWithoutPageModel()
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

        $filter = new PublishingFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    public function testRemovesRouteWhenPageIsNotPublished()
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->atLeastOnce())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($this->mockPageModel(false))
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

        $filter = new PublishingFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    public function testRemovesRouteWhenPageStartDateIsInTheFuture()
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->atLeastOnce())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($this->mockPageModel(true, (string) strtotime('+1 day')))
        ;

        $collection = $this->createMock(RouteCollection::class);

        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn(['bar' => $route])
        ;

        $collection
            ->expects($this->once())
            ->method('remove')
            ->with('bar')
        ;

        $filter = new PublishingFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    public function testRemovesRouteWhenPageStopDateIsInThePast()
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->atLeastOnce())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($this->mockPageModel(true, '', (string) strtotime('-1 day')))
        ;

        $collection = $this->createMock(RouteCollection::class);

        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn(['bar' => $route])
        ;

        $collection
            ->expects($this->once())
            ->method('remove')
            ->with('bar')
        ;

        $filter = new PublishingFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    private function mockTokenChecker(bool $isPreviewMode = false)
    {
        $tokenChecker = $this->createMock(TokenChecker::class);

        $tokenChecker
            ->method('isPreviewMode')
            ->willReturn($isPreviewMode)
        ;

        return $tokenChecker;
    }

    private function mockPageModel(bool $published, string $start = '', string $stop = '')
    {
        return $this->mockClassWithProperties(
            PageModel::class,
            ['published' => $published, 'start' => $start, 'stop' => $stop]
        );
    }
}
