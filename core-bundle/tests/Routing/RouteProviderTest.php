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
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\RouteProvider;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Model\Collection;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class RouteProviderTest extends TestCase
{
    private int $pageModelAutoIncrement = 0;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->pageModelAutoIncrement = 0;
    }

    public function testGetsARouteByName(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->rootId = 1;
        $page->urlPrefix = '';
        $page->language = 'en';
        $page->rootLanguage = 'en';

        $route = new PageRoute($page);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
            ->willReturn($page)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $pageRegistry
            ->expects($this->once())
            ->method('isRoutable')
            ->with($page)
            ->willReturn(true)
        ;

        $framework = $this->mockFramework($pageAdapter);

        $this->assertSame($route, $this->getRouteProvider($framework, $pageRegistry)->getRouteByName('tl_page.17'));
    }

    public function testThrowsAnExceptionIfTheRouteNameDoesNotMatchAPageId(): void
    {
        $provider = $this->getRouteProvider();

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

        $provider = $this->getRouteProvider($this->mockFramework($pageAdapter));

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Page ID "17" not found');

        $provider->getRouteByName('tl_page.17');
    }

    public function testGetsMultipleRoutesByNames(): void
    {
        $page1 = $this->mockClassWithProperties(PageModel::class);
        $page1->id = 17;
        $page1->rootId = 1;
        $page1->urlPrefix = '';
        $page1->urlSuffix = '';
        $page1->language = 'en';
        $page1->rootLanguage = 'en';

        $page2 = $this->mockClassWithProperties(PageModel::class);
        $page2->id = 21;
        $page2->rootId = 1;
        $page2->urlPrefix = '';
        $page2->urlSuffix = '';
        $page2->language = 'en';
        $page2->rootLanguage = 'en';

        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->with('tl_page.id IN (17,21)')
            ->willReturn(new Collection([$page1, $page2], 'tl_page'))
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->exactly(2))
            ->method('getRoute')
            ->withConsecutive([$page1], [$page2])
            ->willReturnOnConsecutiveCalls(new PageRoute($page1), new PageRoute($page2))
        ;

        $pageRegistry
            ->expects($this->exactly(2))
            ->method('isRoutable')
            ->withConsecutive([$page1], [$page2])
            ->willReturn(true)
        ;

        $provider = $this->getRouteProvider($this->mockFramework($pageAdapter), $pageRegistry);
        $routes = $provider->getRoutesByNames(['tl_page.17', 'tl_page.21']);

        $this->assertCount(2, $routes);
    }

    public function testHandlesRoutesWithDomain(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->rootId = 1;
        $page->domain = 'example.org';
        $page->urlPrefix = '';
        $page->language = 'en';
        $page->rootLanguage = 'en';

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
            ->willReturn($page)
        ;

        $route = new PageRoute($page);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $pageRegistry
            ->expects($this->once())
            ->method('isRoutable')
            ->with($page)
            ->willReturn(true)
        ;

        $framework = $this->mockFramework($pageAdapter);

        $this->assertSame($route, $this->getRouteProvider($framework, $pageRegistry)->getRouteByName('tl_page.17'));
    }

    public function testHandlesRoutesWithDomainAndPort(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = 17;
        $page->rootId = 1;
        $page->domain = 'example.org:8080';
        $page->urlPrefix = '';
        $page->language = 'en';
        $page->rootLanguage = 'en';

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(17)
            ->willReturn($page)
        ;

        $route = new PageRoute($page);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $pageRegistry
            ->expects($this->once())
            ->method('isRoutable')
            ->with($page)
            ->willReturn(true)
        ;

        $framework = $this->mockFramework($pageAdapter);

        $this->assertSame($route, $this->getRouteProvider($framework, $pageRegistry)->getRouteByName('tl_page.17'));
    }

    public function testSelectsAllPagesIfNoPageNamesAreGiven(): void
    {
        $pageAdapter = $this->mockAdapter(['findAll']);
        $pageAdapter
            ->expects($this->once())
            ->method('findAll')
        ;

        $provider = $this->getRouteProvider($this->mockFramework($pageAdapter));
        $provider->getRoutesByNames();
    }

    public function testReturnsAnEmptyArrayIfThereAreNoMatchingPages(): void
    {
        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->never())
            ->method('findBy')
        ;

        $provider = $this->getRouteProvider($this->mockFramework($pageAdapter));

        $this->assertSame([], $provider->getRoutesByNames(['foo', 'bar']));
    }

    public function testReturnsAnEmptyCollectionIfThePathContainsAutoItem(): void
    {
        $request = $this->mockRequestWithPath('/foo/auto_item/bar.html');

        $this->assertEmpty($this->getRouteProvider()->getRouteCollectionForRequest($request));
    }

    public function testReturnsAnEmptyCollectionIfTheUrlSuffixDoesNotMatch(): void
    {
        $request = $this->mockRequestWithPath('/foo.php');
        $provider = $this->getRouteProvider($this->mockFramework($this->mockAdapter(['findBy'])));

        $this->assertEmpty($provider->getRouteCollectionForRequest($request));
    }

    public function testReturnsAnEmptyCollectionIfTheLanguageIsNotGiven(): void
    {
        $request = $this->mockRequestWithPath('/foo.html');
        $provider = $this->getRouteProvider($this->mockFramework($this->mockAdapter(['findBy'])));

        $this->assertEmpty($provider->getRouteCollectionForRequest($request));
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

        $framework = $this->mockFramework($pageAdapter);
        $request = $this->mockRequestWithPath('/foo/bar/baz.html', $languages);

        $args = [];
        $routes = [];

        foreach ($pages as $page) {
            $args[] = [$page];
            $routes[] = new PageRoute($page);
        }

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->exactly(\count($pages)))
            ->method('getRoute')
            ->withConsecutive(...$args)
            ->willReturnOnConsecutiveCalls(...$routes)
        ;

        $pageRegistry
            ->expects($this->exactly(\count($pages)))
            ->method('isRoutable')
            ->withConsecutive(...$args)
            ->willReturn(true)
        ;

        $provider = $this->getRouteProvider($framework, $pageRegistry);
        $collection = $provider->getRouteCollectionForRequest($request);

        $this->assertCount(\count($pages), $collection);

        $i = 0;
        ksort($pages);

        foreach ($collection as $name => $route) {
            /** @var PageModel $routedPage */
            $routedPage = $route->getDefault('pageModel');

            $this->assertInstanceOf(PageModel::class, $routedPage);
            $this->assertSame('tl_page.'.$routedPage->id, $name);

            $this->assertSame(
                $pages[$i],
                $routedPage,
                sprintf(
                    'Position %s should be %s/%s but is %s/%s',
                    $i,
                    $pages[$i]->rootLanguage,
                    $pages[$i]->alias,
                    $routedPage->rootLanguage,
                    $routedPage->alias
                )
            );

            ++$i;
        }
    }

    public function getRoutes(): \Generator
    {
        yield 'Sorts host first (1)' => [
            [
                1 => $this->mockPage('en', 'foo'),
                0 => $this->mockPage('en', 'foo', true, 'example.com'),
            ],
            ['en'],
        ];

        yield 'Sorts host first (2)' => [
            [
                $this->mockPage('fr', 'foo', true, 'example.com'),
                $this->mockPage('it', 'foo'),
            ],
            ['en'],
        ];

        yield 'Sorts by language priority (1)' => [
            [
                1 => $this->mockPage('en', 'foo'),
                0 => $this->mockPage('de', 'foo'),
            ],
            ['de', 'en'],
        ];

        yield 'Sorts by language priority (2)' => [
            [
                1 => $this->mockPage('fr', 'foo'),
                0 => $this->mockPage('de', 'foo'),
            ],
            ['en', 'de', 'fr'],
        ];

        yield 'Sorts by language match (1)' => [
            [
                1 => $this->mockPage('de', 'foo'),
                0 => $this->mockPage('fr', 'foo'),
            ],
            ['fr'],
        ];

        yield 'Sorts by language match (2)' => [
            [
                $this->mockPage('it', 'foo'),
                $this->mockPage('de', 'foo'),
            ],
            ['it'],
        ];

        yield 'Sorts by fallback without language' => [
            [
                1 => $this->mockPage('de', 'foo', false),
                0 => $this->mockPage('fr', 'foo'),
            ],
            ['en', 'it'],
        ];

        yield 'Sorts by folder alias' => [
            [
                1 => $this->mockPage('de', 'foo/bar'),
                0 => $this->mockPage('de', 'foo/bar/baz'),
                2 => $this->mockPage('de', 'foo'),
            ],
            ['en'],
        ];

        yield 'Sorts fallback root first' => [
            [
                2 => $this->mockPage('de', 'foo', false),
                4 => $this->mockPage('en', 'foo', false),
                1 => $this->mockPage('de', 'foo/bar', false),
                0 => $this->mockPage('en', 'foo', true, 'example.com'),
                3 => $this->mockPage('en', 'foo/bar', false),
            ],
            ['de', 'fr'],
        ];

        yield 'Sorts by alias if all of the languages are fallback' => [
            [
                1 => $this->mockPage('en', 'foo'),
                2 => $this->mockPage('ru', 'foo'),
                3 => $this->mockPage('fr', 'foo'),
                0 => $this->mockPage('en', 'foo/bar'),
            ],
            ['de'],
        ];

        yield 'Sorts by alias if none of the languages is fallback' => [
            [
                1 => $this->mockPage('en', 'foo', false),
                2 => $this->mockPage('ru', 'foo', false),
                3 => $this->mockPage('fr', 'foo', false),
                0 => $this->mockPage('en', 'foo/bar', false),
            ],
            ['de'],
        ];

        yield 'Sorts by "de" if "de_CH" is accepted' => [
            [
                1 => $this->mockPage('en', 'foo'),
                0 => $this->mockPage('de', 'foo', false),
            ],
            ['de_CH'],
        ];

        yield 'Converts "de_CH" to "de-CH"' => [
            [
                1 => $this->mockPage('en', 'foo'),
                0 => $this->mockPage('de-CH', 'foo', false),
            ],
            ['de_CH'],
        ];

        yield 'Appends "de" in case "de_CH" is accepted and "de" is not' => [
            [
                3 => $this->mockPage('fr', 'foo', false),
                0 => $this->mockPage('de', 'foo', false),
                1 => $this->mockPage('en', 'foo', false),
                2 => $this->mockPage('it', 'foo'),
            ],
            ['de_CH', 'en'],
        ];

        yield 'Sorts with parameters' => [
            [
                1 => $this->mockPage('de', 'foo/bar{!parameters}'),
                0 => $this->mockPage('de', 'foo/bar/baz{!parameters}'),
                2 => $this->mockPage('de', 'foo{!parameters}'),
            ],
            ['en'],
        ];

        yield 'Sorts with absolute path' => [
            [
                1 => $this->mockPage('de', 'foo/bar{!parameters}'),
                0 => $this->mockPage('de', 'foo/{category}/{alias}'),
                2 => $this->mockPage('de', 'foo{!parameters}'),
            ],
            ['en'],
        ];
    }

    /**
     * @dataProvider getRootRoutes
     */
    public function testSortsTheRootRoutes(array $pages, array $languages, array $expectedNames): void
    {
        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(new Collection(array_values($pages), 'tl_page'), null)
        ;

        $framework = $this->mockFramework($pageAdapter);
        $request = $this->mockRequestWithPath('/', $languages);

        $args = [];
        $routes = [];

        foreach ($pages as $page) {
            $args[] = [$page];
            $routes[] = new PageRoute($page);
        }

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->exactly(\count($pages)))
            ->method('getRoute')
            ->withConsecutive(...$args)
            ->willReturnOnConsecutiveCalls(...$routes)
        ;

        $provider = $this->getRouteProvider($framework, $pageRegistry);
        $collection = $provider->getRouteCollectionForRequest($request);

        $this->assertCount(\count($expectedNames), $collection);

        $i = 0;
        $c = \count($pages);
        ksort($pages);

        foreach ($collection as $name => $route) {
            /** @var PageModel $routedPage */
            $routedPage = $route->getDefault('pageModel');

            if ($i > $c - 1) {
                $suffix = '.fallback';
                $page = $pages[$i - $c];
            } else {
                $suffix = '.root';
                $page = $pages[$i];
            }

            $this->assertInstanceOf(PageModel::class, $routedPage);
            $this->assertSame('tl_page.'.$page->id.$suffix, $name);

            $this->assertSame(
                $page,
                $routedPage,
                sprintf(
                    'Position %s should be %s/%s but is %s/%s',
                    $i,
                    $page->rootLanguage,
                    $page->alias,
                    $routedPage->rootLanguage,
                    $routedPage->alias
                )
            );

            ++$i;
        }
    }

    public function getRootRoutes(): \Generator
    {
        $pages = [
            2 => $this->mockRootPage('en', 'english-root'),
            1 => $this->mockPage('en', 'index'),
            0 => $this->mockRootPage('de', 'german-root', false),
        ];

        $routeNames = [
            'tl_page.'.$pages[0]->id.'.root',
            'tl_page.'.$pages[1]->id.'.root',
            'tl_page.'.$pages[2]->id.'.root',
        ];

        yield [
            $pages,
            ['de', 'en'],
            $routeNames,
        ];

        $pages = [
            2 => $this->mockRootPage('en', 'english-root'),
            1 => $this->mockPage('en', 'index'),
            0 => $this->mockRootPage('de', 'german-root', false),
        ];

        $pages[0]->urlPrefix = 'en';
        $pages[1]->urlPrefix = 'en';
        $pages[2]->urlPrefix = 'de';

        $routeNames = [
            'tl_page.'.$pages[0]->id.'.root',
            'tl_page.'.$pages[1]->id.'.root',
            'tl_page.'.$pages[2]->id.'.root',
            'tl_page.'.$pages[0]->id.'.fallback',
            'tl_page.'.$pages[1]->id.'.fallback',
            'tl_page.'.$pages[2]->id.'.fallback',
        ];

        yield [
            $pages,
            ['de', 'en'],
            $routeNames,
        ];
    }

    /**
     * @dataProvider getPageRoutes
     */
    public function testAddsRoutesForAPage(string $alias, string $language, string $domain, string $urlSuffix, bool $prependLocale, string|null $scheme): void
    {
        $pageModel = $this->mockPage($language, $alias, true, $domain, $scheme, $urlSuffix);
        $pageModel->urlPrefix = $prependLocale ? $language : '';

        $pageModel
            ->expects($this->atLeastOnce())
            ->method('loadDetails')
        ;

        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->willReturn(new Collection([$pageModel], 'tl_page'))
        ;

        $framework = $this->mockFramework($pageAdapter);
        $request = $this->mockRequestWithPath(($prependLocale ? '/'.$language : '').'/foo/bar'.$urlSuffix);

        $route = new PageRoute($pageModel);
        $route->setPath(sprintf('/%s{parameters}', $pageModel->alias ?: $pageModel->id));
        $route->setDefault('parameters', '/foo/bar');
        $route->setRequirement('parameters', $pageModel->requireItem ? '/.+' : '(/.+)?');

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($pageModel)
            ->willReturn($route)
        ;

        $pageRegistry
            ->expects($this->once())
            ->method('isRoutable')
            ->with($pageModel)
            ->willReturn(true)
        ;

        $provider = $this->getRouteProvider($framework, $pageRegistry);
        $collection = $provider->getRouteCollectionForRequest($request);

        $this->assertCount(1, $collection);
        $this->assertArrayHasKey('tl_page.'.$pageModel->id, $collection->all());
        $this->assertSame($route, $collection->get('tl_page.'.$pageModel->id));
    }

    public function testDoesNotAddRouteForUnroutablePage(): void
    {
        $routablePage = $this->mockClassWithProperties(PageModel::class);
        $routablePage->id = 17;
        $routablePage->rootId = 1;
        $routablePage->urlPrefix = '';
        $routablePage->language = 'en';
        $routablePage->rootLanguage = 'en';

        $unroutablePage = $this->mockClassWithProperties(PageModel::class);
        $unroutablePage->id = 18;
        $unroutablePage->rootId = 1;
        $unroutablePage->urlPrefix = '';
        $unroutablePage->language = 'en';
        $unroutablePage->rootLanguage = 'en';

        $route = new PageRoute($routablePage);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->exactly(2))
            ->method('findByPk')
            ->withConsecutive([17], [18])
            ->willReturnOnConsecutiveCalls($routablePage, $unroutablePage)
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($routablePage)
            ->willReturn($route)
        ;

        $pageRegistry
            ->expects($this->exactly(2))
            ->method('isRoutable')
            ->withConsecutive([$routablePage], [$unroutablePage])
            ->willReturnOnConsecutiveCalls(true, false)
        ;

        $framework = $this->mockFramework($pageAdapter);

        $this->assertSame($route, $this->getRouteProvider($framework, $pageRegistry)->getRouteByName('tl_page.17'));

        $this->expectException(RouteNotFoundException::class);

        $this->getRouteProvider($framework, $pageRegistry)->getRouteByName('tl_page.18');
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

    public function testIgnoresRoutesWithoutRootId(): void
    {
        $page = $this->mockPage('de', 'foo');
        $page->rootId = 0;

        $page
            ->expects($this->once())
            ->method('loadDetails')
        ;

        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockFramework($pageAdapter);
        $request = $this->mockRequestWithPath('/foo.html');

        $routes = $this->getRouteProvider($framework)->getRouteCollectionForRequest($request)->all();

        $this->assertIsArray($routes);
        $this->assertEmpty($routes);
    }

    public function testIgnoresPagesWithNoRootPageFoundException(): void
    {
        $page = $this->mockPage('de', 'foo');
        $page
            ->expects($this->once())
            ->method('loadDetails')
            ->willThrowException(new NoRootPageFoundException())
        ;

        $pageAdapter = $this->mockAdapter(['findBy']);
        $pageAdapter
            ->expects($this->once())
            ->method('findBy')
            ->willReturn(new Collection([$page], 'tl_page'))
        ;

        $framework = $this->mockFramework($pageAdapter);
        $request = $this->mockRequestWithPath('/foo.html');

        $routes = $this->getRouteProvider($framework)->getRouteCollectionForRequest($request)->all();

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

        $request
            ->method('getHttpHost')
            ->willReturn('example.com')
        ;

        return $request;
    }

    /**
     * @param Adapter<PageModel> $pageAdapter
     *
     * @return ContaoFramework&MockObject
     */
    private function mockFramework(Adapter|null $pageAdapter = null): ContaoFramework
    {
        return $this->mockContaoFramework([PageModel::class => $pageAdapter]);
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPage(string $language, string $alias, bool $fallback = true, string $domain = '', string|null $scheme = null, string $urlSuffix = '.html'): PageModel
    {
        mt_srand(++$this->pageModelAutoIncrement);

        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = $this->pageModelAutoIncrement;
        $page->rootId = 1;
        $page->type = 'regular';
        $page->alias = $alias;
        $page->domain = $domain;
        $page->urlPrefix = '';
        $page->urlSuffix = $urlSuffix;
        $page->rootLanguage = $language;
        $page->rootIsFallback = $fallback;
        $page->rootUseSSL = 'https' === $scheme;
        $page->rootSorting = mt_rand();

        $page
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        return $page;
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockRootPage(string $language, string $alias, bool $fallback = true): PageModel
    {
        $page = $this->mockClassWithProperties(PageModel::class);
        $page->id = ++$this->pageModelAutoIncrement;
        $page->rootId = 1;
        $page->type = 'root';
        $page->alias = $alias;
        $page->domain = '';
        $page->urlPrefix = '';
        $page->urlSuffix = '.html';
        $page->rootLanguage = $language;
        $page->rootIsFallback = $fallback;
        $page->rootUseSSL = false;
        $page->rootSorting = array_reduce((array) $language, static fn ($c, $i) => $c + \ord($i), 0);

        $page
            ->method('loadDetails')
            ->willReturnSelf()
        ;

        return $page;
    }

    private function getRouteProvider(ContaoFramework|null $framework = null, PageRegistry|null $pageRegistry = null): RouteProvider
    {
        $candidates = $this->createMock(CandidatesInterface::class);
        $candidates
            ->method('getCandidates')
            ->willReturn(['foo'])
        ;

        $framework ??= $this->mockContaoFramework();

        if (null === $pageRegistry) {
            $pageRegistry = $this->createMock(PageRegistry::class);
            $pageRegistry
                ->method('isRoutable')
                ->willReturn(true)
            ;
        }

        return new RouteProvider($framework, $candidates, $pageRegistry);
    }
}
