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

use Contao\CoreBundle\Routing\Matcher\DomainFilter;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DomainFilterTest extends TestCase
{
    public function testRemovesRoutesIfTheHostnameDoesNotMatch(): void
    {
        $routes = [
            'nohost' => $this->mockRouteWithHost(''),
            'foobar' => $this->mockRouteWithHost('foobar.com'),
            'barfoo' => $this->mockRouteWithHost('barfoo.com'),
        ];

        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->exactly(2))
            ->method('all')
            ->willReturn($routes)
        ;

        $collection
            ->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive(['nohost'], ['barfoo'])
        ;

        $request = Request::create('/');
        $request->headers->set('Host', 'foobar.com');

        $filter = new DomainFilter();
        $filter->filter($collection, $request);
    }

    public function testDoesNotRemoveRoutesIfThereAreNoRoutesForTheCurrentHostname(): void
    {
        $routes = [
            'nohost' => $this->mockRouteWithHost(''),
            'foobar' => $this->mockRouteWithHost('foobar.com'),
        ];

        $collection = $this->createMock(RouteCollection::class);
        $collection
            ->expects($this->once())
            ->method('all')
            ->willReturn($routes)
        ;

        $collection
            ->expects($this->never())
            ->method('remove')
        ;

        $request = Request::create('/');
        $request->headers->set('Host', 'barfoo.com');

        $filter = new DomainFilter();
        $filter->filter($collection, $request);
    }

    private function mockRouteWithHost(string $host): Route&MockObject
    {
        $route = $this->createMock(Route::class);
        $route
            ->method('getHost')
            ->willReturn($host)
        ;

        return $route;
    }
}
