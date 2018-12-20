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

use Contao\Config;
use Contao\CoreBundle\Routing\RouteProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class RouteProviderTest extends TestCase
{
    public function testGetsRouteByName()
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17]);
        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
            ->willReturn($page)
        ;

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter);

        $provider = new RouteProvider($framework, $this->createMock(Connection::class));
        $route = $provider->getRouteByName('tl_page.17');

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame($page, $route->getDefault('pageModel'));
    }

    public function testThrowsExceptionIfRouteNameHasNoId()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route name does not match a page ID');

        $provider = new RouteProvider($this->mockContaoFrameworkWithAdapters(), $this->createMock(Connection::class));
        $provider->getRouteByName('foobar');
    }

    public function testThrowsExceptionIfPageIsNotFoundFromRouteName()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Page ID 17 not found');

        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
        ;

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter);

        $provider = new RouteProvider($framework, $this->createMock(Connection::class));
        $provider->getRouteByName('tl_page.17');
    }

    public function testGetsRoutesByNames()
    {
        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->with('tl_page.id IN (17,21)')
            ->willReturn(
                new Collection(
                    [
                        $this->mockClassWithProperties(PageModel::class, ['id' => 17]),
                        $this->mockClassWithProperties(PageModel::class, ['id' => 21]),
                    ],
                    'tl_page'
                )
            )
        ;

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter);

        $provider = new RouteProvider($framework, $this->createMock(Connection::class));
        $routes = $provider->getRoutesByNames(['tl_page.17', 'tl_page.21']);

        $this->assertCount(2, $routes);
    }

    public function testFindsAllPagesForGetRoutesByNamesWithNullArgument()
    {
        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->once())
            ->method('findAll')
        ;

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter);

        $provider = new RouteProvider($framework, $this->createMock(Connection::class));
        $provider->getRoutesByNames(null);
    }

    public function testReturnsEmptyArrayForGetRoutesByNamesWithoutPageIds()
    {
        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->never())
            ->method('findBy')
        ;

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter);

        $provider = new RouteProvider($framework, $this->createMock(Connection::class));
        $this->assertSame([], $provider->getRoutesByNames(['foo', 'bar']));
    }


    public function testReturnsEmptyCollectionIfPathContainsAutoItem()
    {
        $request = $this->mockRequestWithPath('/foo/auto_item/bar.html');

        $provider = new RouteProvider($this->mockContaoFrameworkWithAdapters(), $this->createMock(Connection::class));

        $this->assertEmpty($provider->getRouteCollectionForRequest($request));
    }

    public function testReturnsEmptyCollectionIfUrlSuffixDoesNotMatch()
    {
        $request = $this->mockRequestWithPath('/foo.php');
        $framework = $this->mockContaoFrameworkWithAdapters(
            null,
            $this->mockConfigAdapter(['urlSuffix' => '.html'])
        );

        $provider = new RouteProvider($framework, $this->createMock(Connection::class));

        $this->assertEmpty($provider->getRouteCollectionForRequest($request));
    }

    public function testReturnsEmptyCollectionIfLanguageIsNotInUrl()
    {
        $request = $this->mockRequestWithPath('/foo.html');
        $framework = $this->mockContaoFrameworkWithAdapters(
            null,
            $this->mockConfigAdapter(['urlSuffix' => '.html', 'addLanguageToUrl' => true])
        );

        $provider = new RouteProvider($framework, $this->createMock(Connection::class));

        $this->assertEmpty($provider->getRouteCollectionForRequest($request));
    }

    /**
     * @dataProvider aliasCandidatesProvider
     */
    public function testFindsPagesByAliasCandidates(string $path, string $urlSuffix, bool $addLanguageToUrl, bool $folderUrl, array $aliases, array $ids = [])
    {
        $conditions = [];

        if (!empty($ids)) {
            $conditions[] = 'tl_page.id IN ('.implode(',', $ids).')';
        }

        if (!empty($aliases)) {
            $conditions[] = 'tl_page.alias IN ('.implode(',', $aliases).')';
        }

        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->with([implode(' OR ', $conditions)], [])
            ->willReturn(null)
        ;

        $configAdapter = $this->mockConfigAdapter(
            [
                'urlSuffix' => $urlSuffix,
                'addLanguageToUrl' => $addLanguageToUrl,
                'folderUrl' => $folderUrl,
            ]
        );

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter, $configAdapter);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quote')
            ->willReturnArgument(0)
        ;

        $request = $this->mockRequestWithPath($path);

        $provider = new RouteProvider($framework, $connection);

        $provider->getRouteCollectionForRequest($request);
    }

    public function aliasCandidatesProvider()
    {
        yield [
            '/foo.html',
            '.html',
            false,
            false,
            ['foo'],
        ];

        yield [
            '/bar.php',
            '.php',
            false,
            false,
            ['bar'],
        ];

        yield [
            '/foo/bar.html',
            '.html',
            false,
            false,
            ['foo'],
        ];

        yield [
            '/de/foo.html',
            '.html',
            true,
            false,
            ['foo'],
        ];

        yield [
            '/de/foo/bar.html',
            '.html',
            true,
            false,
            ['foo'],
        ];

        yield [
            '/foo/bar.html',
            '.html',
            false,
            true,
            ['foo/bar', 'foo'],
        ];

        yield [
            '/foo/bar/baz/some/more.html',
            '.html',
            false,
            true,
            ['foo/bar/baz/some/more', 'foo/bar/baz/some', 'foo/bar/baz', 'foo/bar', 'foo'],
        ];

        yield [
            '/de/foo/bar.html',
            '.html',
            true,
            true,
            ['foo/bar', 'foo'],
        ];

        yield [
            '/15.html',
            '.html',
            false,
            false,
            [],
            [15],
        ];

        yield [
            '/de/15.html',
            '.html',
            true,
            false,
            [],
            [15],
        ];

        yield [
            '/15/foo.html',
            '.html',
            false,
            true,
            ['15/foo'],
            [15],
        ];
    }

    /**
     * @dataProvider sortRoutesProvider
     */
    public function testSortsRoutes(array $pages, array $languages)
    {
        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->willReturn(new Collection(array_values($pages), 'tl_page'));
        ;

        $configAdapter = $this->mockConfigAdapter(
            [
                'urlSuffix' => '.html',
                'addLanguageToUrl' => false,
                'folderUrl' => false,
            ]
        );

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter, $configAdapter);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quote')
            ->willReturnArgument(0)
        ;

        $request = $this->mockRequestWithPath('/foo.html', $languages);

        $provider = new RouteProvider($framework, $connection);
        $collection = $provider->getRouteCollectionForRequest($request);

        $this->assertCount(count($pages), $collection);
        $i = 0;

        ksort($pages);

        foreach ($collection as $name => $route) {
            /** @var PageModel $routedPage */
            $expectedPage = $pages[$i++];

            /** @var PageModel $routedPage */
            $routedPage = $route->getDefault('pageModel');

            $this->assertInstanceOf(PageModel::class, $routedPage);
            $this->assertSame('tl_page.'.$routedPage->id, $name);
            $this->assertSame($expectedPage, $routedPage);
        }
    }

    public function sortRoutesProvider()
    {
        yield 'Sorts host first (1)' => [
            [
                1 => $this->createPage('en', 'bar', true, ''),
                0 => $this->createPage('en', 'foo', true, 'example.com'),
            ],
            ['en'],
        ];

        yield 'Sorts host first (2)' => [
            [
                0 => $this->createPage('fr', 'foo', true, 'example.com'),
                1 => $this->createPage('it', 'bar', true, ''),
            ],
            ['en'],
        ];

        yield 'Sorts by language priority (1)' => [
            [
                1 => $this->createPage('en', 'foo'),
                0 => $this->createPage('de', 'bar'),
            ],
            ['de', 'en'],
        ];

        yield 'Sorts by language priority (2)' => [
            [
                1 => $this->createPage('fr', 'foo'),
                0 => $this->createPage('de', 'bar'),
            ],
            ['en', 'de', 'fr'],
        ];

        yield 'Sorts by language match (1)' => [
            [
                1 => $this->createPage('de', 'bar'),
                0 => $this->createPage('fr', 'foo'),
            ],
            ['fr'],
        ];

        yield 'Sorts by language match (2)' => [
            [
                0 => $this->createPage('it', 'foo'),
                1 => $this->createPage('de', 'bar'),
            ],
            ['it'],
        ];

        yield 'Sorts by fallback without language' => [
            [
                1 => $this->createPage('de', 'bar', false),
                0 => $this->createPage('fr', 'foo', true),
            ],
            ['en', 'it'],
        ];

        yield 'Sorts by folder alias' => [
            [
                1 => $this->createPage('de', 'foo/bar'),
                0 => $this->createPage('de', 'foo/bar/baz'),
                2 => $this->createPage('de', 'foo'),
            ],
            ['en'],
        ];

        yield 'Complex sorting (1)' => [
            [
                2 => $this->createPage('de', 'foo'),
                1 => $this->createPage('de', 'foo/bar'),
                0 => $this->createPage('en', 'foo', true, 'example.com'),
                4 => $this->createPage('en', 'foo'),
                3 => $this->createPage('en', 'foo/bar'),
            ],
            ['de', 'fr'],
        ];
    }

    /**
     * @dataProvider routeForPageProvider
     */
    public function testAddsRouteForPage(string $alias, string $language, string $domain, string $urlSuffix, bool $addLanguageToUrl)
    {
        $page = $this->createPage($language, $alias, true, $domain);
        $pageAdapter = $this->mockPageAdapter();

        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->willReturn(new Collection([$page], 'tl_page'));
        ;

        $configAdapter = $this->mockConfigAdapter(
            [
                'urlSuffix' => $urlSuffix,
                'addLanguageToUrl' => $addLanguageToUrl,
                'folderUrl' => false,
            ]
        );

        $framework = $this->mockContaoFrameworkWithAdapters($pageAdapter, $configAdapter);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quote')
            ->willReturnArgument(0)
        ;

        $request = $this->mockRequestWithPath(($addLanguageToUrl ? "/$language" : '')."/foo$urlSuffix");

        $provider = new RouteProvider($framework, $connection);
        $collection = $provider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $collection);
        $route = $collection->get('tl_page.'.$page->id);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('(/.+)?', $route->getRequirement('parameters'));
        $this->assertTrue($route->getOption('utf8'));
        $this->assertSame($domain, $route->getHost());

        if ($addLanguageToUrl) {
            $this->assertSame("/{_locale}/$alias{parameters}$urlSuffix", $route->getPath());
            $this->assertSame($language, $route->getRequirement('_locale'));
        } else {
            $this->assertSame("/$alias{parameters}$urlSuffix", $route->getPath());
        }
    }

    public function routeForPageProvider()
    {
        foreach (['foo', 'foo/bar'] as $alias) {
            foreach (['en', 'de'] as $language) {
                foreach (['', 'example.com'] as $domain) {
                    foreach (['.html', '.php', ''] as $urlSuffix) {
                        foreach ([true, false] as $addLanguageToUrl) {
                            yield [
                                $alias,
                                $language,
                                $domain,
                                $urlSuffix,
                                $addLanguageToUrl,
                            ];
                        }
                    }
                }
            }
        }
    }

    private function mockRequestWithPath(string $path, array $languages = ['en'])
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

    private function mockContaoFrameworkWithAdapters($pageAdapter = null, $configAdapter = null)
    {
        if (null === $pageAdapter) {
            $pageAdapter = $this->mockPageAdapter();
        }

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
            Config::class => $configAdapter,
        ]);

        return $framework;
    }

    private function mockConfigAdapter(array $config)
    {
        $configAdapter = $this->mockAdapter(['get']);

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($param) use ($config) {
                return $config[$param] ?? null;
            })
        ;

        return $configAdapter;
    }

    private function mockPageAdapter()
    {
        $pageAdapter = $this->mockAdapter(['getTable', 'findAll', 'findByPk', 'findBy']);

        $pageAdapter
            ->method('getTable')
            ->willReturn('tl_page')
        ;

        return $pageAdapter;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|PageModel
     */
    private function createPage(string $language, string $alias, bool $fallback = true, string $domain = '')
    {
        return $this->mockClassWithProperties(
            PageModel::class,
            [
                'id' => random_int(1, 10000),
                'type' => 'regular',
                'alias' => $alias,
                'domain' => $domain,
                'rootLanguage' => $language,
                'rootIsFallback' => $fallback,
            ]
        );
    }
}
