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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PublishingFilterTest extends TestCase
{
    public function testDoesNotFilterInPreviewMode(): void
    {
        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->never())
            ->method('all')
        ;

        $filter = new PublishingFilter($this->mockTokenChecker(true));
        $filter->filter($collection, $this->createMock(Request::class));
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

        $filter = new PublishingFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    public function testRemovesRouteWhenPageIsNotPublished(): void
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

    public function testRemovesRouteWhenPageStartDateIsInTheFuture(): void
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

    public function testRemovesRouteWhenPageStopDateIsInThePast(): void
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

    /**
     * @return TokenChecker|MockObject
     */
    private function mockTokenChecker(bool $isPreviewMode = false): TokenChecker
    {
        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->method('isPreviewMode')
            ->willReturn($isPreviewMode)
        ;

        return $tokenChecker;
    }

    /**
     * @return PageModel|MockObject
     */
    private function mockPageModel(bool $published, string $start = '', string $stop = ''): PageModel
    {
        return $this->mockClassWithProperties(PageModel::class, compact('published', 'start', 'stop'));
    }
}
