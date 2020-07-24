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

        $this->assertSame('/foo/bar{!parameters}.baz', $route->getPath());
        $this->assertSame('/items/news', $route->getDefault('parameters'));
        $this->assertSame('(/.+)?', $route->getRequirement('parameters'));
    }

    public function testCreatesParameteredPageRouteIfPathIsNullWithRequireItem(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => 'bar',
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

        $this->assertSame('/foo/bar{!parameters}.baz', $route->getPath());
        $this->assertSame('/items/news', $route->getDefault('parameters'));
        $this->assertSame('/.+', $route->getRequirement('parameters'));
    }

    /**
     * @dataProvider pageRouteWithPathProvider
     */
    public function testCreatesPageRouteWithPath(RouteConfig $config, string $urlPrefix, string $alias, string $urlSuffix, string $expectedPath): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'foo',
            'alias' => $alias,
            'urlPrefix' => $urlPrefix,
            'urlSuffix' => $urlSuffix,
        ]);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRouteConfig')
            ->with('foo')
            ->willReturn($config)
        ;

        $this->pageRegistry
            ->expects($this->once())
            ->method('enhancePageRoute')
            ->willReturnArgument(0)
        ;

        $route = $this->factory->createRouteForPage($pageModel);

        $this->assertSame($expectedPath, $route->getPath());
    }

    public function pageRouteWithPathProvider(): \Generator
    {
        yield 'Does not add parameters for empty path' => [
            new RouteConfig(''),
            'foo',
            'bar',
            '.baz',
            '/foo/bar.baz',
        ];

        yield 'Prepends the page alias for a relative path' => [
            new RouteConfig('{alias}'),
            'foo',
            'bar',
            '.baz',
            '/foo/bar/{alias}.baz',
        ];

        yield 'URL Suffix from route config overrides the page settings' => [
            new RouteConfig('{alias}', null, '.html'),
            'foo',
            'bar',
            '.baz',
            '/foo/bar/{alias}.html',
        ];

        yield 'Adds URL suffix for absolute path' => [
            new RouteConfig('/foo'),
            '',
            'bar',
            '.baz',
            '/foo.baz',
        ];

        yield 'Adds URL prefix and suffix for absolute path' => [
            new RouteConfig('/not-bar'),
            'foo',
            'bar',
            '.baz',
            '/foo/not-bar.baz',
        ];

        yield 'Override URL Suffix for absolute path' => [
            new RouteConfig('/foo', null, '.html'),
            '',
            'bar',
            '.baz',
            '/foo.html',
        ];

        yield 'Allow config with full path' => [
            new RouteConfig('/feed/{alias}.atom', null, ''),
            '',
            'bar',
            '.baz',
            '/feed/{alias}.atom',
        ];

        yield 'Adds URL prefix to config with full path' => [
            new RouteConfig('/feed/{alias}.atom', null, ''),
            'foo',
            'bar',
            '.baz',
            '/foo/feed/{alias}.atom',
        ];
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
