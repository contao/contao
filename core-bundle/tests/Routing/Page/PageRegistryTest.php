<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Page;

use Contao\CoreBundle\Routing\Page\ContentCompositionInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;

class PageRegistryTest extends TestCase
{
    public function testReturnsRouteConfig(): void
    {
        $config = new RouteConfig();

        $registry = new PageRegistry();
        $registry->add('foo', $config);

        $this->assertSame($config, $registry->getRouteConfig('foo'));
    }

    public function testReturnsEmptyRouteConfigForUnknownType(): void
    {
        $config = new RouteConfig();

        $registry = new PageRegistry();
        $registry->add('foo', $config);

        $result = $registry->getRouteConfig('bar');
        $this->assertNotSame($config, $result);
    }

    public function testReturnsConfigKeys(): void
    {
        $registry = new PageRegistry();
        $registry->add('foo', new RouteConfig());
        $registry->add('bar', new RouteConfig());

        $this->assertSame(['foo', 'bar'], $registry->keys());
    }

    public function testReturnsRouteIfEnhancerIsNotFound(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);

        $route = $this->createMock(PageRoute::class);
        $route
            ->expects($this->once())
            ->method('getPageModel')
            ->willReturn($pageModel)
        ;

        $registry = new PageRegistry();
        $registry->add('foo', new RouteConfig());

        $this->assertSame($route, $registry->enhancePageRoute($route));
    }

    public function testUsesRouteEnhancerForPageType(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);

        $route = $this->createMock(PageRoute::class);
        $route
            ->expects($this->once())
            ->method('getPageModel')
            ->willReturn($pageModel)
        ;

        $enhancer1 = $this->createMock(DynamicRouteInterface::class);
        $enhancer1
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->with($route)
            ->willReturn($route)
        ;

        $enhancer2 = $this->createMock(DynamicRouteInterface::class);
        $enhancer2
            ->expects($this->never())
            ->method($this->anything())
        ;

        $registry = new PageRegistry();
        $registry->add('foo', new RouteConfig(), $enhancer1);
        $registry->add('bar', new RouteConfig(), $enhancer2);

        $this->assertSame($route, $registry->enhancePageRoute($route));
    }

    public function testGetsSuffixesFromRouteEnhancers(): void
    {
        $enhancer1 = $this->createMock(DynamicRouteInterface::class);
        $enhancer1
            ->expects($this->once())
            ->method('getUrlSuffixes')
            ->willReturn(['foo', 'bar'])
        ;

        $enhancer2 = $this->createMock(DynamicRouteInterface::class);
        $enhancer2
            ->expects($this->once())
            ->method('getUrlSuffixes')
            ->willReturn(['baz'])
        ;

        $enhancer3 = $this->createMock(DynamicRouteInterface::class);
        $enhancer3
            ->expects($this->once())
            ->method('getUrlSuffixes')
            ->willReturn(['', 'foo'])
        ;

        $registry = new PageRegistry();
        $registry->add('foo', new RouteConfig(), $enhancer1);
        $registry->add('bar', new RouteConfig(), $enhancer2);
        $registry->add('baz', new RouteConfig(), $enhancer3);

        $this->assertSame(['foo', 'bar', 'baz', ''], $registry->getUrlSuffixes());
    }

    public function testSupportsContentCompositionReturnsTrueForUnknownType(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);
        $registry = new PageRegistry();

        $this->assertTrue($registry->supportsContentComposition($pageModel));

        $registry->add('bar', new RouteConfig());

        $this->assertTrue($registry->supportsContentComposition($pageModel));
    }

    public function testSupportsContentComposition(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);

        $composite = $this->createMock(ContentCompositionInterface::class);
        $composite
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($pageModel)
            ->willReturn(true)
        ;

        $registry = new PageRegistry();
        $registry->add('foo', new RouteConfig(), null, $composite);

        $this->assertTrue($registry->supportsContentComposition($pageModel));
    }

    public function testOverwritesExistingTypes(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);

        $config1 = new RouteConfig();
        $config2 = new RouteConfig();

        $enhancer1 = $this->createMock(DynamicRouteInterface::class);
        $enhancer1
            ->expects($this->never())
            ->method($this->anything())
        ;

        $enhancer2 = $this->createMock(DynamicRouteInterface::class);
        $enhancer2
            ->expects($this->once())
            ->method('getUrlSuffixes')
            ->willReturn([])
        ;

        $composite1 = $this->createMock(ContentCompositionInterface::class);
        $composite1
            ->expects($this->never())
            ->method($this->anything())
        ;

        $composite2 = $this->createMock(ContentCompositionInterface::class);
        $composite2
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->willReturn(true)
        ;

        $registry = new PageRegistry();
        $registry->add('foo', $config1, $enhancer1, $composite1);
        $registry->add('foo', $config2, $enhancer2, $composite2);

        $this->assertSame($config2, $registry->getRouteConfig('foo'));
        $registry->getUrlSuffixes();
        $registry->supportsContentComposition($pageModel);
    }

    public function testRemovesType(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);
        $config = new RouteConfig();

        $enhancer = $this->createMock(DynamicRouteInterface::class);
        $enhancer
            ->expects($this->never())
            ->method($this->anything())
        ;

        $composite = $this->createMock(ContentCompositionInterface::class);
        $composite
            ->expects($this->never())
            ->method($this->anything())
        ;

        $registry = new PageRegistry();
        $registry->add('foo', $config, $enhancer, $composite);
        $registry->remove('foo');

        $this->assertNotSame($config, $registry->getRouteConfig('foo'));
        $registry->getUrlSuffixes();
        $registry->supportsContentComposition($pageModel);
    }
}
