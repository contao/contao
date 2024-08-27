<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\EventListener\DataContainer\PageRoutingListener;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\CompiledRoute;
use Twig\Environment;

class PageRoutingListenerTest extends TestCase
{
    /**
     * @dataProvider routePathProvider
     */
    public function testGetsPathFromPageRoute(string $path, array $requirements, string $expected): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);
        $pageRoute = $this->mockPageRoute($path, $requirements);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($pageModel)
            ->willReturn($pageRoute)
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@ContaoCore/Backend/be_route_path.html.twig',
                [
                    'path' => $expected,
                ],
            )
            ->willReturn('foobar')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 42]);

        $listener = new PageRoutingListener($framework, $pageRegistry, $twig);
        $listener->generateRoutePath($dc);
    }

    public static function routePathProvider(): iterable
    {
        yield 'Path without parameters' => [
            'foobar',
            [],
            'foobar',
        ];

        yield 'Ignores unknown parameters in path' => [
            'foo/bar/{baz}.html',
            [],
            'foo/bar/{baz}.html',
        ];

        yield 'Ignores unknown parameters' => [
            'foo/bar/{baz}.html',
            ['bar' => 'baz'],
            'foo/bar/{baz}.html',
        ];

        yield 'Replaces parameter' => [
            'foo/{bar}.html',
            ['bar' => '.+'],
            'foo/{<span class="tl_tip" title=".+">bar</span>}.html',
        ];

        yield 'Replaces parameters' => [
            'foo/{bar}/{baz}.html',
            ['bar' => '.+', 'baz' => '\d+'],
            'foo/{<span class="tl_tip" title=".+">bar</span>}/{<span class="tl_tip" title="\d+">baz</span>}.html',
        ];

        yield 'Handles parameters starting with exclamation point' => [
            'foo/{!bar}.html',
            ['bar' => '.+'],
            'foo/{<span class="tl_tip" title=".+">bar</span>}.html',
        ];
    }

    public function testReturnsEmptyPathIfPageModelIsNotFound(): void
    {
        $pageAdapter = $this->mockAdapter(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->never())
            ->method('getRoute')
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 42]);
        $listener = new PageRoutingListener($framework, $pageRegistry, $this->createMock(Environment::class));

        $this->assertSame('', $listener->generateRoutePath($dc));
    }

    public function testGeneratesRoutingConflicts(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 1,
            'alias' => 'foobar',
            'urlPrefix' => '',
            'urlSuffix' => '',
            'domain' => '',
        ]);

        $aliasPages = [
            $this->mockClassWithProperties(PageModel::class, [
                'id' => 2,
                'alias' => 'foobar',
                'urlPrefix' => '',
                'urlSuffix' => '',
                'domain' => '',
            ]),
            $this->mockClassWithProperties(PageModel::class, [
                'id' => 3,
                'alias' => 'foobar',
                'urlPrefix' => '',
                'urlSuffix' => '',
                'domain' => '',
            ]),
            $this->mockClassWithProperties(PageModel::class, [
                'id' => 4,
                'alias' => 'foobar',
                'urlPrefix' => '',
                'urlSuffix' => '',
                'domain' => '',
            ]),
        ];

        $pageRoutes = [$this->mockPageRouteFromPageModel($pageModel)];

        foreach ($aliasPages as $aliasPage) {
            $pageRoutes[] = $this->mockPageRouteFromPageModel($aliasPage);
        }

        $pageAdapter = $this->mockAdapter(['findWithDetails', 'findSimilarByAlias']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($pageModel)
        ;

        $pageAdapter
            ->expects($this->once())
            ->method('findSimilarByAlias')
            ->with($pageModel)
            ->willReturn($aliasPages)
        ;

        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->expects($this->exactly(3))
            ->method('addToUrl')
            ->withConsecutive(
                ['act=edit&id=2&popup=1&nb=1'],
                ['act=edit&id=3&popup=1&nb=1'],
                ['act=edit&id=4&popup=1&nb=1'],
            )
            ->willReturn('editUrl')
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
            Backend::class => $backendAdapter,
        ]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->exactly(3))
            ->method('isRoutable')
            ->willReturn(true)
        ;

        $pageRegistry
            ->expects($this->exactly(4))
            ->method('getRoute')
            ->willReturn(...$pageRoutes)
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 1]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@ContaoCore/Backend/be_route_conflicts.html.twig',
                [
                    'conflicts' => [
                        [
                            'page' => $aliasPages[0],
                            'path' => '/foobar',
                            'editUrl' => 'editUrl',
                        ],
                        [
                            'page' => $aliasPages[1],
                            'path' => '/foobar',
                            'editUrl' => 'editUrl',
                        ],
                        [
                            'page' => $aliasPages[2],
                            'path' => '/foobar',
                            'editUrl' => 'editUrl',
                        ],
                    ],
                ],
            )
            ->willReturn('foobar')
        ;

        $listener = new PageRoutingListener($framework, $pageRegistry, $twig);

        $this->assertSame('foobar', $listener->generateRouteConflicts($dc));
    }

    public function testReturnsEmptyRoutingConflictsIfPageModelIsNotFound(): void
    {
        $pageAdapter = $this->mockAdapter(['findWithDetails', 'findSimilarByAlias']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(42)
            ->willReturn(null)
        ;

        $pageAdapter
            ->expects($this->never())
            ->method('findSimilarByAlias')
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->never())
            ->method($this->anything())
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 42]);
        $listener = new PageRoutingListener($framework, $pageRegistry, $twig);

        $this->assertSame('', $listener->generateRouteConflicts($dc));
    }

    public function testReturnsEmptyRoutingConflictsIfNoSimilarPagesAreFound(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageAdapter = $this->mockAdapter(['findWithDetails', 'findSimilarByAlias']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $pageAdapter
            ->expects($this->once())
            ->method('findSimilarByAlias')
            ->with($pageModel)
            ->willReturn(null)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->never())
            ->method($this->anything())
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method($this->anything())
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 42]);
        $listener = new PageRoutingListener($framework, $pageRegistry, $twig);

        $this->assertSame('', $listener->generateRouteConflicts($dc));
    }

    public function testSkipsSimilarPageIfItIsNotRoutable(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 1,
            'alias' => 'foobar',
            'urlPrefix' => '',
            'urlSuffix' => '',
            'domain' => '',
        ]);

        $aliasPage = $this->mockClassWithProperties(PageModel::class, [
            'id' => 2,
            'alias' => 'foobar',
            'urlPrefix' => '',
            'urlSuffix' => '',
            'domain' => '',
        ]);

        $pageAdapter = $this->mockAdapter(['findWithDetails', 'findSimilarByAlias']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($pageModel)
        ;

        $pageAdapter
            ->expects($this->once())
            ->method('findSimilarByAlias')
            ->with($pageModel)
            ->willReturn([$aliasPage])
        ;

        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
            Backend::class => $backendAdapter,
        ]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('isRoutable')
            ->with($aliasPage)
            ->willReturn(false)
        ;

        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($this->mockPageRouteFromPageModel($pageModel))
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 1]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render')
        ;

        $listener = new PageRoutingListener($framework, $pageRegistry, $twig);

        $this->assertSame('', $listener->generateRouteConflicts($dc));
    }

    public function testSkipsSimilarPageIfDomainDoesNotMatch(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 1,
            'alias' => 'foobar',
            'urlPrefix' => '',
            'urlSuffix' => '',
            'domain' => 'example.com',
        ]);

        $aliasPage = $this->mockClassWithProperties(PageModel::class, [
            'id' => 2,
            'alias' => 'foobar',
            'urlPrefix' => '',
            'urlSuffix' => '',
            'domain' => 'example.org',
        ]);

        $pageAdapter = $this->mockAdapter(['findWithDetails', 'findSimilarByAlias']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($pageModel)
        ;

        $pageAdapter
            ->expects($this->once())
            ->method('findSimilarByAlias')
            ->with($pageModel)
            ->willReturn([$aliasPage])
        ;

        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
            Backend::class => $backendAdapter,
        ]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->never())
            ->method('isRoutable')
        ;

        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->willReturn($this->mockPageRouteFromPageModel($pageModel))
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 1]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render')
        ;

        $listener = new PageRoutingListener($framework, $pageRegistry, $twig);

        $this->assertSame('', $listener->generateRouteConflicts($dc));
    }

    public function testSkipsSimilarPageIfUrlDoesNotMatch(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'id' => 1,
            'alias' => 'foobar',
            'urlPrefix' => 'de',
            'urlSuffix' => '',
            'domain' => '',
        ]);

        $aliasPage = $this->mockClassWithProperties(PageModel::class, [
            'id' => 2,
            'alias' => 'foobar',
            'urlPrefix' => 'en',
            'urlSuffix' => '',
            'domain' => '',
        ]);

        $pageAdapter = $this->mockAdapter(['findWithDetails', 'findSimilarByAlias']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(1)
            ->willReturn($pageModel)
        ;

        $pageAdapter
            ->expects($this->once())
            ->method('findSimilarByAlias')
            ->with($pageModel)
            ->willReturn([$aliasPage])
        ;

        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->expects($this->never())
            ->method('addToUrl')
        ;

        $framework = $this->mockContaoFramework([
            PageModel::class => $pageAdapter,
            Backend::class => $backendAdapter,
        ]);

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('isRoutable')
            ->willReturn(true)
        ;

        $pageRoutes = [
            $this->mockPageRouteFromPageModel($pageModel),
            $this->mockPageRouteFromPageModel($aliasPage),
        ];

        $pageRegistry
            ->expects($this->exactly(2))
            ->method('getRoute')
            ->willReturn(...$pageRoutes)
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 1]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render')
        ;

        $listener = new PageRoutingListener($framework, $pageRegistry, $twig);

        $this->assertSame('', $listener->generateRouteConflicts($dc));
    }

    private function mockPageRoute(string $path, array $requirements = []): PageRoute&MockObject
    {
        $route = $this->createMock(PageRoute::class);
        $route
            ->expects($this->once())
            ->method('getPath')
            ->willReturn($path)
        ;

        $route
            ->expects($this->once())
            ->method('getRequirements')
            ->willReturn($requirements)
        ;

        return $route;
    }

    private function mockPageRouteFromPageModel(PageModel&MockObject $pageModel): PageRoute&MockObject
    {
        $url = '/'.$pageModel->alias.$pageModel->urlSuffix;
        $staticPrefix = '/'.$pageModel->alias;

        if ($pageModel->urlPrefix) {
            $url = '/'.$pageModel->urlPrefix.$url;
            $staticPrefix = '/'.$pageModel->urlPrefix.$staticPrefix;
        }

        $route = $this->createMock(PageRoute::class);
        $route
            ->method('getPath')
            ->willReturn($url)
        ;

        $route
            ->method('getRequirements')
            ->willReturn([])
        ;

        $route
            ->method('getUrlPrefix')
            ->willReturn($pageModel->urlPrefix)
        ;

        $route
            ->method('getUrlSuffix')
            ->willReturn($pageModel->urlSuffix)
        ;

        $route
            ->method('compile')
            ->willReturn(new CompiledRoute($staticPrefix, '', [], []))
        ;

        return $route;
    }
}
