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
use Contao\CoreBundle\Routing\ContentResolvingGenerator;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;

class ContentResolvingGeneratorTest extends TestCase
{
    /**
     * @var ContentRouteProviderInterface&MockObject
     */
    private $provider1;

    /**
     * @var ContentRouteProviderInterface&MockObject
     */
    private $provider2;

    /**
     * @var ContentResolvingGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->provider1 = $this->createMock(ContentRouteProviderInterface::class);
        $this->provider2 = $this->createMock(ContentRouteProviderInterface::class);

        $this->generator = new ContentResolvingGenerator([$this->provider1, $this->provider2]);
    }

    public function testThrowsExceptionIfRouteNameIsNotSupported(): void
    {
        $this->expectException(ContentRouteNotFoundException::class);

        $this->generator->generate('foo');
    }

    public function testThrowsExceptionIfContentParameterIsNotSet(): void
    {
        $this->expectException(ContentRouteNotFoundException::class);

        $this->generator->generate(PageRoute::ROUTE_NAME);
    }

    public function testGeneratesTheContentObjectWithoutResolvingIfItIsARoute(): void
    {
        $content = new Route('/foobar');

        $url = $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content]
        );

        $this->assertSame('/foobar', $url);
    }

    public function testUsesTheFirstResolverThatSupportsTheContent(): void
    {
        $content = (object) ['foo' => 'bar'];

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
            ->willReturn(new Route('/foobar'))
        ;

        $this->provider2
            ->expects($this->never())
            ->method($this->anything())
        ;

        $url = $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content]
        );

        $this->assertSame('/foobar', $url);
    }

    public function testIteratesOverTheResolvers(): void
    {
        $content = (object) ['foo' => 'bar'];

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
            ->willReturn(new Route('/foobar'))
        ;

        $url = $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content]
        );

        $this->assertSame('/foobar', $url);
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

        $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content]
        );
    }

    public function testGeneratesTheContentRoute(): void
    {
        $content = (object) ['foo' => 'bar'];

        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, [
            'id' => 17,
            'alias' => 'foobar',
            'type' => 'foo',
            'domain' => 'www.example.com',
            'rootUseSSL' => true,
            'urlPrefix' => 'some-language',
            'urlSuffix' => '.html',
        ]);

        $route = new PageRoute($page);

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

        $url = $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/some-language/foobar.html', $url);
    }
}
