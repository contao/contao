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
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\RouteProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class RouteProviderTest extends TestCase
{
    public function testGetsARouteByName(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17]);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
            ->willReturn($page)
        ;

        $framework = $this->mockFramework($pageAdapter);
        $route = $this->mockRouteProvider($framework)->getRouteByName('tl_page.17');

        $this->assertSame($page, $route->getDefault('pageModel'));
    }

    public function testThrowsAnExceptionIfTheRouteNameDoesNotMatchAPageId(): void
    {
        $provider = $this->mockRouteProvider();

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route name does not match a page ID');

        $provider->getRouteByName('foobar');
    }

    public function testThrowsAnExceptionIfThePageIdIsInvalid(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
        ;

        $provider = $this->mockRouteProvider($this->mockFramework($pageAdapter));

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Page ID "17" not found');

        $provider->getRouteByName('tl_page.17');
    }

    public function testGetsMultipleRoutesByNames(): void
    {
        $pageAdapter = $this->mockAdapter(['findBy']);
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

        $provider = $this->mockRouteProvider($this->mockFramework($pageAdapter));
        $routes = $provider->getRoutesByNames(['tl_page.17', 'tl_page.21']);

        $this->assertCount(2, $routes);
    }

    public function testHandlesRoutesWithDomain(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17, 'domain' => 'example.org']);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
            ->willReturn($page)
        ;

        $framework = $this->mockFramework($pageAdapter);
        $route = $this->mockRouteProvider($framework)->getRouteByName('tl_page.17');

        $this->assertSame('example.org', $route->getHost());
    }

    public function testHandlesRoutesWithDomainAndPort(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['id' => 17, 'domain' => 'example.org:8080']);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
            ->willReturn($page)
        ;

        $framework = $this->mockFramework($pageAdapter);
        $route = $this->mockRouteProvider($framework)->getRouteByName('tl_page.17');

        $this->assertSame('example.org:8080', $route->getHost());
    }

    public function testSelectsAllPagesIfNoPageNamesAreGiven(): void
    {
        $pageAdapter = $this->mockAdapter(['findAll']);
        $pageAdapter
            ->expects($this->once())
            ->method('findAll')
        ;

        $provider = $this->mockRouteProvider($this->mockFramework($pageAdapter));
        $provider->getRoutesByNames(null);
    }

    public function testReturnsAnEmptyArrayIfThereAreNoMatchingPages(): void
    {
        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->never())
            ->method('findBy')
        ;

        $provider = $this->mockRouteProvider($this->mockFramework($pageAdapter));

        $this->assertSame([], $provider->getRoutesByNames(['foo', 'bar']));
    }

    public function testReturnsAnEmptyCollectionIfThePathContainsAutoItem(): void
    {
        $request = $this->mockRequestWithPath('/foo/auto_item/bar.html');

        $this->assertEmpty($this->mockRouteProvider()->getRouteCollectionForRequest($request));
    }

    public function testReturnsAnEmptyCollectionIfTheUrlSuffixDoesNotMatch(): void
    {
        $request = $this->mockRequestWithPath('/foo.php');

        $this->assertEmpty($this->mockRouteProvider()->getRouteCollectionForRequest($request));
    }

    public function testReturnsAnEmptyCollectionIfTheLanguageIsNotGiven(): void
    {
        $request = $this->mockRequestWithPath('/foo.html');
        $provider = $this->mockRouteProvider(null, '.html', true);

        $this->assertEmpty($provider->getRouteCollectionForRequest($request));
    }

    /**
     * @dataProvider getAliasCandidates
     */
    public function testFindsPagesByAliasCandidates(string $path, string $urlSuffix, bool $prependLocale, bool $folderUrl, array $aliases, array $ids = []): void
    {
        $conditions = [];

        if (!empty($ids)) {
            $conditions[] = 'tl_page.id IN ('.implode(',', $ids).')';
        }

        if (!empty($aliases)) {
            $conditions[] = 'tl_page.alias IN ('.implode(',', $aliases).')';
        }

        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->with([implode(' OR ', $conditions)], [])
            ->willReturn(null)
        ;

        $configAdapter = $this->mockConfigAdapter(compact('folderUrl'));
        $framework = $this->mockFramework($pageAdapter, $configAdapter);
        $request = $this->mockRequestWithPath($path);

        $provider = $this->mockRouteProvider($framework, $urlSuffix, $prependLocale);
        $provider->getRouteCollectionForRequest($request);
    }

    public function getAliasCandidates(): \Generator
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
     * @dataProvider getRoutes
     */
    public function testSortsTheRoutes(array $pages, array $languages): void
    {
        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->willReturn(new Collection(array_values($pages), 'tl_page'))
        ;

        $configAdapter = $this->mockConfigAdapter(['folderUrl' => false]);
        $framework = $this->mockFramework($pageAdapter, $configAdapter);
        $request = $this->mockRequestWithPath('/foo.html', $languages);

        $provider = $this->mockRouteProvider($framework);
        $collection = $provider->getRouteCollectionForRequest($request);

        $this->assertCount(\count($pages), $collection);

        $i = 0;
        ksort($pages);

        foreach ($collection as $name => $route) {
            /** @var PageModel $routedPage */
            $routedPage = $route->getDefault('pageModel');

            $this->assertInstanceOf(PageModel::class, $routedPage);
            $this->assertSame('tl_page.'.$routedPage->id, $name);
            $this->assertSame($pages[$i++], $routedPage);
        }
    }

    public function getRoutes(): \Generator
    {
        yield 'Sorts host first (1)' => [
            [
                1 => $this->createPage('en', 'bar'),
                0 => $this->createPage('en', 'foo', true, 'example.com'),
            ],
            ['en'],
        ];

        yield 'Sorts host first (2)' => [
            [
                0 => $this->createPage('fr', 'foo', true, 'example.com'),
                1 => $this->createPage('it', 'bar'),
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
                0 => $this->createPage('fr', 'foo'),
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

        yield 'Sorts fallback root first' => [
            [
                2 => $this->createPage('de', 'foo', false),
                4 => $this->createPage('en', 'foo', false),
                1 => $this->createPage('de', 'foo/bar', false),
                0 => $this->createPage('en', 'foo', true, 'example.com'),
                3 => $this->createPage('en', 'foo/bar', false),
            ],
            ['de', 'fr'],
        ];

        // createPage() generates a rootSorting value from the language, so the test order is by language
        yield 'Sorts by root page sorting if none of the languages is fallback' => [
            [
                1 => $this->createPage('en', 'foo', false),
                3 => $this->createPage('ru', 'foo', false),
                2 => $this->createPage('fr', 'foo', false),
                0 => $this->createPage('en', 'foo/bar', false),
            ],
            ['de'],
        ];
    }

    /**
     * @dataProvider getPageRoutes
     */
    public function testAddsRoutesForAPage(string $alias, string $language, string $domain, string $urlSuffix, bool $prependLocale, ?string $scheme): void
    {
        $page = $this->createPage($language, $alias, true, $domain, $scheme);

        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $configAdapter = $this->mockConfigAdapter(['folderUrl' => false]);
        $framework = $this->mockFramework($pageAdapter, $configAdapter);
        $request = $this->mockRequestWithPath(($prependLocale ? '/'.$language : '').'/foo'.$urlSuffix);

        $provider = $this->mockRouteProvider($framework, $urlSuffix, $prependLocale);
        $collection = $provider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $collection);

        $route = $collection->get('tl_page.'.$page->id);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertSame('(/.+)?', $route->getRequirement('parameters'));
        $this->assertTrue($route->getOption('utf8'));
        $this->assertSame($domain, $route->getHost());

        if ('https' === $scheme) {
            $this->assertSame(['https'], $route->getSchemes());
        } else {
            $this->assertSame([], $route->getSchemes());
        }

        if ($prependLocale) {
            $this->assertSame('/{_locale}/'.$alias.'{parameters}'.$urlSuffix, $route->getPath());
            $this->assertSame($language, $route->getRequirement('_locale'));
        } else {
            $this->assertSame('/'.$alias.'{parameters}'.$urlSuffix, $route->getPath());
        }
    }

    public function getPageRoutes(): \Generator
    {
        foreach (['foo', 'foo/bar'] as $alias) {
            foreach (['en', 'de'] as $language) {
                foreach (['', 'example.com'] as $domain) {
                    foreach (['.html', '.php', ''] as $urlSuffix) {
                        foreach ([true, false] as $addLanguageToUrl) {
                            foreach ([null, 'https'] as $scheme) {
                                yield [
                                    $alias,
                                    $language,
                                    $domain,
                                    $urlSuffix,
                                    $addLanguageToUrl,
                                    $scheme,
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return Request|MockObject
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

    /**
     * @return ContaoFramework|MockObject
     */
    private function mockFramework(Adapter $pageAdapter = null, Adapter $configAdapter = null): ContaoFramework
    {
        return $this->mockContaoFramework([PageModel::class => $pageAdapter, Config::class => $configAdapter]);
    }

    /**
     * @return Adapter|MockObject
     */
    private function mockConfigAdapter(array $config): Adapter
    {
        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->willReturnCallback(
                function ($param) use ($config) {
                    return $config[$param] ?? null;
                }
            )
        ;

        return $configAdapter;
    }

    /**
     * @return PageModel|MockObject
     */
    private function createPage(string $language, string $alias, bool $fallback = true, string $domain = '', string $scheme = null): PageModel
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
                'rootUseSSL' => 'https' === $scheme,
                'rootSorting' => array_reduce((array) $language, function ($c, $i) { return $c + \ord($i); }, 0),
            ]
        );
    }

    private function mockRouteProvider(ContaoFramework $framework = null, string $urlSuffix = '.html', bool $prependLocale = false): RouteProvider
    {
        if (null === $framework) {
            $framework = $this->mockContaoFramework();
        }

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quote')
            ->willReturnArgument(0)
        ;

        return new RouteProvider($framework, $connection, $urlSuffix, $prependLocale);
    }
}
