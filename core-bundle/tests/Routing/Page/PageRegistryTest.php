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
use Contao\CoreBundle\Routing\Page\DynamicRouteInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\MockObject\MockObject;

class PageRegistryTest extends TestCase
{
    public function testReturnsRouteConfig(): void
    {
        $config = new RouteConfig();

        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('foo', $config);

        $this->assertSame($config, $registry->getRouteConfig('foo'));
    }

    public function testReturnsEmptyRouteConfigForUnknownType(): void
    {
        $config = new RouteConfig();

        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('foo', $config);

        $result = $registry->getRouteConfig('bar');
        $this->assertNotSame($config, $result);
    }

    public function testReturnsConfigKeys(): void
    {
        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('foo', new RouteConfig());
        $registry->add('bar', new RouteConfig());

        $this->assertSame(['foo', 'bar'], $registry->keys());
    }

    public function testGetPathRegex(): void
    {
        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('foo', new RouteConfig('', '/foo'));
        $registry->add('bar', new RouteConfig('', '/bar/[a-z]+'));
        $registry->add('baz', new RouteConfig());

        $this->assertSame(['foo' => '/foo', 'bar' => '/bar/[a-z]+'], $registry->getPathRegex());
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

        $registry = new PageRegistry($this->createMock(Connection::class));
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

        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('foo', new RouteConfig(), $enhancer1);
        $registry->add('bar', new RouteConfig(), $enhancer2);

        $this->assertSame($route, $registry->enhancePageRoute($route));
    }

    public function testGetUrlPrefixes(): void
    {
        $connection = $this->mockConnectionWithPrefixAndSuffix('en');
        $registry = new PageRegistry($connection);

        $this->assertSame(['en'], $registry->getUrlPrefixes());
    }

    public function testGetsUrlSuffixes(): void
    {
        $connection = $this->mockConnectionWithPrefixAndSuffix('', 'foo');

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
            ->willReturn(['foo', 'baz', ''])
        ;

        $registry = new PageRegistry($connection);
        $registry->add('bar', new RouteConfig(), $enhancer1);
        $registry->add('baz', new RouteConfig(), $enhancer2);
        $registry->add('baz', new RouteConfig('', null, '.html'));

        $this->assertSame(['foo', '.html', 'bar', 'baz', ''], $registry->getUrlSuffixes());
    }

    public function testSupportsContentCompositionReturnsTrueForUnknownType(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);
        $registry = new PageRegistry($this->createMock(Connection::class));

        $this->assertTrue($registry->supportsContentComposition($pageModel));

        $registry->add('bar', new RouteConfig());

        $this->assertTrue($registry->supportsContentComposition($pageModel));
    }

    public function testSupportsContentCompositionWithBoolean(): void
    {
        /** @var PageModel&MockObject $fooModel */
        $fooModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);

        /** @var PageModel&MockObject $barModel */
        $barModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'bar']);

        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('foo', new RouteConfig(), null, false);
        $registry->add('bar', new RouteConfig(), null, true);

        $this->assertFalse($registry->supportsContentComposition($fooModel));
        $this->assertTrue($registry->supportsContentComposition($barModel));
    }

    public function testSupportsContentCompositionFromPage(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'foo']);

        $page = $this->createMock(ContentCompositionInterface::class);
        $page
            ->expects($this->once())
            ->method('supportsContentComposition')
            ->with($pageModel)
            ->willReturn(true)
        ;

        $registry = new PageRegistry($this->createMock(Connection::class));
        $registry->add('foo', new RouteConfig(), null, $page);

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

        $registry = new PageRegistry($this->mockConnectionWithPrefixAndSuffix());
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

        $registry = new PageRegistry($this->mockConnectionWithPrefixAndSuffix());
        $registry->add('foo', $config, $enhancer, $composite);
        $registry->remove('foo');

        $this->assertNotSame($config, $registry->getRouteConfig('foo'));
        $registry->getUrlSuffixes();
        $registry->supportsContentComposition($pageModel);
    }

    private function mockConnectionWithPrefixAndSuffix(string $urlPrefix = '', string $urlSuffix = '.html'): Connection
    {
        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn([['urlPrefix' => $urlPrefix, 'urlSuffix' => $urlSuffix]])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('query')
            ->with("SELECT urlPrefix, urlSuffix FROM tl_page WHERE type='root'")
            ->willReturn($statement)
        ;

        return $connection;
    }
}
