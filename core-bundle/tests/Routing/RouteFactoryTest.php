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

use Contao\CoreBundle\Exception\ContentRouteNotFoundException;
use Contao\CoreBundle\Routing\Content\ContentRouteProviderInterface;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\Page\RouteConfig;
use Contao\CoreBundle\Routing\RouteFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Route;

class RouteFactoryTest extends TestCase
{
    /**
     * @var PageRegistry&MockObject
     */
    private $pageRegistry;

    /**
     * @var ContentRouteProviderInterface&MockObject
     */
    private $provider1;

    /**
     * @var ContentRouteProviderInterface&MockObject
     */
    private $provider2;
    /**
     * @var RouteFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->pageRegistry = $this->createMock(PageRegistry::class);
        $this->provider1 = $this->createMock(ContentRouteProviderInterface::class);
        $this->provider2 = $this->createMock(ContentRouteProviderInterface::class);

        $this->factory = new RouteFactory($this->pageRegistry, [$this->provider1, $this->provider2]);
    }

    public function testCreatesParameteredPageRouteIfPathIsNullWithoutRequireItem(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => 'bar',
            'parameters' => '/this/is/{ignored}',
            'urlPrefix' => 'foo',
            'urlSuffix' => '.baz',
            'requireItem' => '',
        ]);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRouteConfig')
            ->with('foo')
            ->willReturn(new RouteConfig())
        ;

        $this->pageRegistry
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->willReturnArgument(0)
        ;

        $route = $this->factory->createRouteForPage($pageModel, '/items/news');

        $this->assertSame('/foo/bar{parameters}.baz', $route->getPath());
        $this->assertSame('/items/news', $route->getDefault('parameters'));
        $this->assertSame('(/.+)?', $route->getRequirement('parameters'));
    }

    public function testCreatesParameteredPageRouteIfPathIsNullWithRequireItem(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => 'bar',
            'parameters' => '/this/is/{ignored}',
            'urlPrefix' => 'foo',
            'urlSuffix' => '.baz',
            'requireItem' => '1',
        ]);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRouteConfig')
            ->with('foo')
            ->willReturn(new RouteConfig())
        ;

        $this->pageRegistry
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->willReturnArgument(0)
        ;

        $route = $this->factory->createRouteForPage($pageModel, '/items/news');

        $this->assertSame('/foo/bar{parameters}.baz', $route->getPath());
        $this->assertSame('/items/news', $route->getDefault('parameters'));
        $this->assertSame('/.+', $route->getRequirement('parameters'));
    }

    public function testCreatesPageRouteWithoutPathParameters(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => 'bar',
            'urlPrefix' => 'foo',
            'urlSuffix' => '.baz',
        ]);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRouteConfig')
            ->with('foo')
            ->willReturn(new RouteConfig(''))
        ;

        $this->pageRegistry
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->willReturnArgument(0)
        ;

        $route = $this->factory->createRouteForPage($pageModel);

        $this->assertSame('/foo/bar.baz', $route->getPath());
    }

    public function testCreatesPageRouteWithParameters(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => 'bar',
            'parameters' => '',
            'urlPrefix' => 'foo',
            'urlSuffix' => '.baz',
        ]);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRouteConfig')
            ->with('foo')
            ->willReturn(new RouteConfig('/{alias}'))
        ;

        $this->pageRegistry
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->willReturnArgument(0)
        ;

        $route = $this->factory->createRouteForPage($pageModel);

        $this->assertSame('/foo/bar/{alias}.baz', $route->getPath());
    }

    public function testCreatesPageRouteWithParametersFromPageModel(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => 'bar',
            'parameters' => '/{category}/{alias}',
            'urlPrefix' => 'foo',
            'urlSuffix' => '.baz',
        ]);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRouteConfig')
            ->with('foo')
            ->willReturn(new RouteConfig('/{alias}'))
        ;

        $this->pageRegistry
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->willReturnArgument(0)
        ;

        $route = $this->factory->createRouteForPage($pageModel);

        $this->assertSame('/foo/bar/{category}/{alias}.baz', $route->getPath());
    }

    public function testCreatesPageRouteWithContent(): void
    {
        $content = (object) ['foo' => 'bar'];

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => 'bar',
            'urlPrefix' => 'foo',
            'urlSuffix' => '.baz',
        ]);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRouteConfig')
            ->with('foo')
            ->willReturn(new RouteConfig())
        ;

        $this->pageRegistry
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->willReturnArgument(0)
        ;

        /** @var PageRoute $route */
        $route = $this->factory->createRouteForPage($pageModel, '', $content);

        $this->assertSame($content, $route->getContent());
    }

    public function testGeneratesTheContentObjectWithoutResolvingIfItIsARoute(): void
    {
        $content = new Route('/foobar');

        $this->provider1
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->provider2
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->assertSame($content, $this->factory->createRouteForContent($content));
    }

    public function testUsesTheFirstResolverThatSupportsTheContent(): void
    {
        $content = (object) ['foo' => 'bar'];
        $route = new Route('/foobar');

        $this->provider1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(true)
        ;

        $this->provider1
            ->expects($this->once())
            ->method('getRouteForContent')
            ->with($content)
            ->willReturn($route)
        ;

        $this->provider2
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->assertSame($route, $this->factory->createRouteForContent($content));
    }

    public function testIteratesOverTheResolvers(): void
    {
        $content = (object) ['foo' => 'bar'];
        $route = new Route('/foobar');

        $this->provider1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(false)
        ;

        $this->provider1
            ->expects($this->never())
            ->method('getRouteForContent')
        ;

        $this->provider2
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(true)
        ;

        $this->provider2
            ->expects($this->once())
            ->method('getRouteForContent')
            ->with($content)
            ->willReturn($route)
        ;

        $this->assertSame($route, $this->factory->createRouteForContent($content));
    }

    public function testThrowsExceptionIfNoResolverSupportsTheContent(): void
    {
        $content = (object) ['foo' => 'bar'];

        $this->provider1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(false)
        ;

        $this->provider2
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(false)
        ;

        $this->expectException(ContentRouteNotFoundException::class);

        $this->factory->createRouteForContent($content);
    }
}
