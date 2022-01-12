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
use Twig\Environment;

class PageRoutingListenerTest extends TestCase
{
    public function testGetsPathFromPageRoute(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $pageRoute = $this->createMock(PageRoute::class);
        $pageRoute
            ->expects($this->once())
            ->method('getPath')
            ->willReturn('foobar')
        ;

        $pageRegistry = $this->createMock(PageRegistry::class);
        $pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($pageModel)
            ->willReturn($pageRoute)
        ;

        $dc = $this->mockClassWithProperties(DataContainer::class, ['id' => 42]);
        $listener = new PageRoutingListener($framework, $pageRegistry, $this->createMock(Environment::class));

        $this->assertSame('foobar', $listener->loadRoutePath('', $dc));
    }

    public function testReturnsEmptyPathIfPageModelIsNotFound(): void
    {
        $pageAdapter = $this->mockAdapter(['findByPk']);
        $pageAdapter
            ->expects($this->once())
            ->method('findByPk')
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

        $this->assertSame('', $listener->loadRoutePath('foobar', $dc));
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

        $aliasRoutes = [
            $this->createMock(PageRoute::class),
            $this->createMock(PageRoute::class),
            $this->createMock(PageRoute::class),
        ];

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
            ->withConsecutive(...array_map(static fn ($page) => [$page], $aliasPages))
            ->willReturn(true)
        ;

        $pageRegistry
            ->expects($this->exactly(3))
            ->method('getRoute')
            ->withConsecutive(...array_map(static fn ($page) => [$page], $aliasPages))
            ->willReturn(...$aliasRoutes)
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
                            'route' => $aliasRoutes[0],
                            'editUrl' => 'editUrl',
                        ],
                        [
                            'page' => $aliasPages[1],
                            'route' => $aliasRoutes[1],
                            'editUrl' => 'editUrl',
                        ],
                        [
                            'page' => $aliasPages[2],
                            'route' => $aliasRoutes[2],
                            'editUrl' => 'editUrl',
                        ],
                    ],
                ]
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
            ->expects($this->never())
            ->method('getRoute')
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
            ->expects($this->never())
            ->method('getRoute')
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
            ->expects($this->never())
            ->method('isRoutable')
        ;

        $pageRegistry
            ->expects($this->never())
            ->method('getRoute')
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
}
