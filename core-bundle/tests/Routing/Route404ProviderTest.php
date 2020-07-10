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

use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Routing\Route404Provider;
use Contao\CoreBundle\Routing\RouteFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Cmf\Component\Routing\Candidates\Candidates;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class Route404ProviderTest extends TestCase
{
    public function testGetRouteByNameThrowsException(): void
    {
        $provider = new Route404Provider(
            $this->mockContaoFramework(),
            $this->createMock(Connection::class),
            $this->createMock(Candidates::class),
            $this->createMock(RouteFactory::class)
        );

        $this->expectException(RouteNotFoundException::class);

        $provider->getRouteByName('foo');
    }

    public function testGetRoutesByNamesWithValueReturnsEmptyArray(): void
    {
        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->never())
            ->method('findByType')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $this->createMock(Candidates::class),
            $this->createMock(RouteFactory::class)
        );

        $result = $provider->getRoutesByNames(['foo']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetRoutesByNamesWithoutValueReturnsAllRoutes(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->rootId = 1;

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $candidates = $this->createMock(Candidates::class);
//        $candidates
//            ->expects($this->once())
//            ->method('getCandidates')
//            ->willReturn([])
//        ;

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

        $result = $provider->getRoutesByNames(null);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testDoesNotCheckCandidatesForEmptyPath(): void
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

        $candidates = $this->createMock(Candidates::class);
        $candidates
            ->expects($this->never())
            ->method('getCandidates')
        ;

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

        $this->assertCount(0, $provider->getRouteCollectionForRequest($request));
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
        $request = $this->mockRequestWithPath('/foo');

        $candidates = $this->createMock(Candidates::class);
        $candidates
            ->expects($this->once())
            ->method('getCandidates')
            ->with($request)
            ->willReturn([])
        ;

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

        $this->assertCount(0, $provider->getRouteCollectionForRequest($request));
    }

    public function testCreatesOneRouteWithoutLocale(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->rootId = 1;
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

        $candidates = $this->createMock(Candidates::class);

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

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
        $page->rootId = 1;
        $page->domain = 'example.com';
        $page->rootUseSSL = true;
        $page->rootLanguage = 'de';
        $page->urlPrefix = 'de';

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $request = $this->mockRequestWithPath('/');

        $candidates = $this->createMock(Candidates::class);

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

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
        $this->assertTrue($route->getOption('utf8'));
        $this->assertSame('example.com', $route->getHost());
        $this->assertSame(['https'], $route->getSchemes());
        $this->assertSame('/de/{_url_fragment}', $route->getPath());
    }

//    public function testLocale()
//    {
//        $this->assertArrayHasKey('tl_page.'.$pageModel->id.'.locale', $collection->all());
//        $route = $collection->get('tl_page.'.$pageModel->id.'.locale');
//
//        $this->assertInstanceOf(Route::class, $route);
//        $this->assertSame('(/.+)?', $route->getRequirement('parameters'));
//        $this->assertSame('/foo/bar', $route->getDefault('parameters'));
//        $this->assertTrue($route->getOption('utf8'));
//        $this->assertSame($domain, $route->getHost());
//
//        if ('https' === $scheme) {
//            $this->assertSame(['https'], $route->getSchemes());
//        } else {
//            $this->assertSame([], $route->getSchemes());
//        }
//
//        $this->assertSame('/'.$alias.'{parameters}'.$urlSuffix, $route->getPath());
//        $this->assertSame(RedirectController::class.'::urlRedirectAction', $route->getDefault('_controller'));
//        $this->assertTrue($route->getDefault('permanent'));
//
//        // It's correct to test for the language twice here: the prefix is added *to the current request URL*
//        $this->assertSame('/'.$language.'/'.$language.'/foo/bar'.$urlSuffix, $route->getDefault('path'));
//    }

    /**
     * @dataProvider sortRoutesProvider
     */
    public function testCorrectlySortRoutes(array $expectedRoutes, array $languages, array ...$pagesData): void
    {
        $pages = [];

        foreach ($pagesData as $row) {
            $pages[] = $this->mockClassWithProperties(
                PageModel::class,
                array_merge(
                    [
                        'domain' => '',
                        'rootId' => 1,
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

        $candidates = $this->createMock(Candidates::class);

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

        $routes = $provider->getRouteCollectionForRequest($request)->all();

        $this->assertCount(\count($expectedRoutes), $routes);

        foreach (array_keys($routes) as $name) {
            $this->assertSame(array_shift($expectedRoutes), $name);
        }
    }

    public function sortRoutesProvider(): \Generator
    {
        yield 'adds page' => [
            ['tl_page.42.error_404'],
            ['en'],
            ['id' => 42, 'urlPrefix' => ''],
        ];

        yield 'sorts page with locale first' => [
            ['tl_page.42.error_404.locale', 'tl_page.42.error_404'],
            ['en'],
            ['id' => 42, 'urlPrefix' => 'en'],
        ];

        yield 'sorts page with domain first' => [
            ['tl_page.42.error_404', 'tl_page.17.error_404'],
            ['en'],
            ['id' => 17, 'urlPrefix' => ''],
            ['id' => 42, 'domain' => 'example.com', 'urlPrefix' => ''],
        ];

        yield 'sorts pages with locales first' => [
            ['tl_page.42.error_404.locale', 'tl_page.17.error_404.locale', 'tl_page.42.error_404', 'tl_page.17.error_404'],
            ['en'],
            ['id' => 17, 'urlPrefix' => 'en'],
            ['id' => 42, 'domain' => 'example.com', 'urlPrefix' => 'en'],
        ];

        yield 'sorts pages by preferred locale' => [
            ['tl_page.42.error_404', 'tl_page.17.error_404'],
            ['de'],
            ['id' => 17, 'rootLanguage' => 'en', 'urlPrefix' => ''],
            ['id' => 42, 'rootLanguage' => 'de', 'urlPrefix' => ''],
        ];
    }

    public function testIgnoresRoutesWithoutRootId(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;

        $page
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $request = $this->mockRequestWithPath('/');

        $candidates = $this->createMock(Candidates::class);

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

        $routes = $provider->getRouteCollectionForRequest($request)->all();

        $this->assertIsArray($routes);
        $this->assertEmpty($routes);
    }

    public function testIgnoresPagesWithNoRootPageFoundException(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->rootId = 1;

        $page
            ->expects($this->once())
            ->method('loadDetails')
            ->willThrowException(new NoRootPageFoundException())
        ;

        $pageAdapter = $this->mockAdapter(['findByType']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByType')
            ->with('error_404')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $request = $this->mockRequestWithPath('/');

        $candidates = $this->createMock(Candidates::class);

        $provider = new Route404Provider(
            $framework,
            $this->createMock(Connection::class),
            $candidates,
            $this->createMock(RouteFactory::class)
        );

        $routes = $provider->getRouteCollectionForRequest($request)->all();

        $this->assertIsArray($routes);
        $this->assertEmpty($routes);
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
