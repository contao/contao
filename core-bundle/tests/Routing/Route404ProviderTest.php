<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing;

use Contao\CoreBundle\Routing\Route404Provider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class Route404ProviderTest extends TestCase
{
    public function testGetRouteByNameThrowsException(): void
    {
        $framework = $this->mockContaoFramework();
        $provider = new Route404Provider($framework, false);

        $this->expectException(RouteNotFoundException::class);

        $provider->getRouteByName('foo');
    }

    public function testGetRoutesByNamesReturnsEmptyArray(): void
    {
        $framework = $this->mockContaoFramework();

        $provider = new Route404Provider($framework, false);
        $result = $provider->getRoutesByNames(['foo']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testReturnsEmptyCollectionWithout404Pages(): void
    {
        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $request = $this->mockRequestWithPath('/');

        $provider = new Route404Provider($framework, false);
        $this->assertEquals(0, $provider->getRouteCollectionForRequest($request)->count());
    }

    public function testCreatesOneRouteWithoutLocale(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->domain = 'example.com';
        $page->rootUseSSL = true;

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $request = $this->mockRequestWithPath('/');

        $provider = new Route404Provider($framework, false);
        $routes = $provider->getRouteCollectionForRequest($request)->all();

        $this->assertCount(1, $routes);
        $this->assertArrayHasKey('tl_page.17.error_404', $routes);

        $route = $routes['tl_page.17.error_404'];

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('.*', $route->getRequirement('_url_fragment'));
        $this->assertTrue($route->getOption('utf8'));
        $this->assertSame('example.com', $route->getHost());
        $this->assertSame(['https'], $route->getSchemes());
        $this->assertSame('/{_url_fragment}', $route->getPath());
    }

    public function testCreatesTwoRoutesWithLocale(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->domain = 'example.com';
        $page->rootUseSSL = true;
        $page->rootLanguage = 'de';

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $request = $this->mockRequestWithPath('/');

        $provider = new Route404Provider($framework, true);
        $routes = $provider->getRouteCollectionForRequest($request)->all();

        $this->assertCount(2, $routes);
        $this->assertArrayHasKey('tl_page.17.error_404', $routes);
        $this->assertArrayHasKey('tl_page.17.error_404.locale', $routes);

        $route = $routes['tl_page.17.error_404'];
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('.*', $route->getRequirement('_url_fragment'));
        $this->assertTrue($route->getOption('utf8'));
        $this->assertSame('example.com', $route->getHost());
        $this->assertSame(['https'], $route->getSchemes());
        $this->assertSame('/{_url_fragment}', $route->getPath());

        $route = $routes['tl_page.17.error_404.locale'];
        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('.*', $route->getRequirement('_url_fragment'));
        $this->assertSame('de', $route->getRequirement('_locale'));
        $this->assertTrue($route->getOption('utf8'));
        $this->assertSame('example.com', $route->getHost());
        $this->assertSame(['https'], $route->getSchemes());
        $this->assertSame('/{_locale}/{_url_fragment}', $route->getPath());
    }

    /**
     * @dataProvider sortRoutesProvider
     */
    public function testCorrectlySortRoutes(array $expectedRoutes, array $languages, bool $prependLocale, array ...$pagesData): void
    {
        $pages = [];

        foreach ($pagesData as $row) {
            $pages[] = $this->mockClassWithProperties(
                PageModel::class,
                array_merge(
                    [
                        'domain' => '',
                        'rootUseSSL' => false,
                        'rootLanguage' => 'de',
                        'rootIsFallback' => true,
                        'rootSorting' => 0,
                    ],
                    $row
                )
            );
        }

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(new Collection($pages, 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $request = $this->mockRequestWithPath('/', $languages);

        $provider = new Route404Provider($framework, $prependLocale);
        $routes = $provider->getRouteCollectionForRequest($request)->all();

        $this->assertCount(\count($expectedRoutes), $routes);

        foreach ($routes as $name => $route) {
            $this->assertSame(array_shift($expectedRoutes), $name);
        }
    }

    public function sortRoutesProvider(): \Generator
    {
        yield 'adds page' => [
            ['tl_page.42.error_404'],
            ['en'],
            false,
            ['id' => 42],
        ];

        yield 'sorts page with locale first' => [
            ['tl_page.42.error_404.locale', 'tl_page.42.error_404'],
            ['en'],
            true,
            ['id' => 42],
        ];

        yield 'sorts page with domain first' => [
            ['tl_page.42.error_404', 'tl_page.17.error_404'],
            ['en'],
            false,
            ['id' => 17],
            ['id' => 42, 'domain' => 'example.com'],
        ];

        yield 'sorts pages with locales first' => [
            ['tl_page.42.error_404.locale', 'tl_page.17.error_404.locale', 'tl_page.42.error_404', 'tl_page.17.error_404'],
            ['en'],
            true,
            ['id' => 17],
            ['id' => 42, 'domain' => 'example.com'],
        ];

        yield 'sorts pages by preferred locale' => [
            ['tl_page.42.error_404', 'tl_page.17.error_404'],
            ['de'],
            false,
            ['id' => 17, 'rootLanguage' => 'en'],
            ['id' => 42, 'rootLanguage' => 'de'],
        ];
    }

    /**
     * @return Request&MockObject
     */
    private function mockRequestWithPath(string $path, array $languages = ['en']): Request
    {
        $request = $this->createMock(Request::class);
        $request
            ->method('getPathInfo')
            ->willReturn($path)
        ;

        $request
            ->method('getLanguages')
            ->willReturn($languages)
        ;

        return $request;
    }
}
