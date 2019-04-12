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
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class PublishedFilterTest extends TestCase
{
    public function testDoesNotFilterInPreviewMode(): void
    {
        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->never())
            ->method('all')
        ;

        $filter = new PublishedFilter($this->mockTokenChecker(true));
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

        $collection
            ->expects($this->never())
            ->method('remove')
        ;

        $filter = new PublishedFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    public function testRemovesARouteIfThePageHasNotBeenPublished(): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->atLeastOnce())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($this->mockClassWithProperties(PageModel::class, ['rootIsPublic' => false]))
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

        $filter = new PublishedFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    public function testDoesNotRemoveARouteIfThePageHasBeenPublished(): void
    {
        $route = $this->createMock(Route::class);
        $route
            ->expects($this->atLeastOnce())
            ->method('getDefault')
            ->with('pageModel')
            ->willReturn($this->mockClassWithProperties(PageModel::class, ['rootIsPublic' => true]))
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

        $filter = new PublishedFilter($this->mockTokenChecker());
        $filter->filter($collection, $this->createMock(Request::class));
    }

    /**
     * @return TokenChecker|MockObject
     */
    private function mockTokenChecker(bool $isPreviewMode = false): TokenChecker
    {
        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->method('hasBackendUser')
            ->willReturn($isPreviewMode)
        ;

        $tokenChecker
            ->method('isPreviewMode')
            ->willReturn($isPreviewMode)
        ;

        return $tokenChecker;
    }
}
