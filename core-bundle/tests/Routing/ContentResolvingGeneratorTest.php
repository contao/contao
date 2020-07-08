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

use Contao\CoreBundle\ContentRouting\ContentRoute;
use Contao\CoreBundle\ContentRouting\ContentUrlResolverInterface;
use Contao\CoreBundle\ContentRouting\PageProviderInterface;
use Contao\CoreBundle\Exception\ContentRouteNotFoundException;
use Contao\CoreBundle\Routing\ContentResolvingGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Route;

class ContentResolvingGeneratorTest extends TestCase
{
    /**
     * @var ContentUrlResolverInterface&MockObject
     */
    private $resolver1;

    /**
     * @var ContentUrlResolverInterface&MockObject
     */
    private $resolver2;

    /**
     * @var ServiceLocator&MockObject
     */
    private $providers;

    /**
     * @var ContentResolvingGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->resolver1 = $this->createMock(ContentUrlResolverInterface::class);
        $this->resolver2 = $this->createMock(ContentUrlResolverInterface::class);
        $this->providers = $this->createMock(ServiceLocator::class);

        $this->generator = new ContentResolvingGenerator([$this->resolver1, $this->resolver2], $this->providers);
    }

    public function testThrowsExceptionIfRouteNameIsNotSupported(): void
    {
        $this->expectException(ContentRouteNotFoundException::class);

        $this->generator->generate('foo');
    }

    public function testThrowsExceptionIfContentParameterIsNotSet(): void
    {
        $this->expectException(ContentRouteNotFoundException::class);

        $this->generator->generate(ContentRoute::ROUTE_NAME);
    }

    public function testGeneratesTheContentObjectWithoutResolvingIfItIsARoute(): void
    {
        $content = new Route('/foobar');

        $url = $this->generator->generate(
            ContentRoute::ROUTE_NAME,
            [ContentRoute::CONTENT_PARAMETER => $content]
        );

        $this->assertSame('/foobar', $url);
    }

    public function testUsesTheFirstResolverThatSupportsTheContent(): void
    {
        $content = (object) ['foo' => 'bar'];

        $this->resolver1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(true)
        ;

        $this->resolver1
            ->expects($this->once())
            ->method('resolveContent')
            ->with($content)
            ->willReturn(new Route('/foobar'))
        ;

        $this->resolver2
            ->expects($this->never())
            ->method($this->anything())
        ;

        $url = $this->generator->generate(
            ContentRoute::ROUTE_NAME,
            [ContentRoute::CONTENT_PARAMETER => $content]
        );

        $this->assertSame('/foobar', $url);
    }

    public function testIteratesOverTheResolvers(): void
    {
        $content = (object) ['foo' => 'bar'];

        $this->resolver1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(false)
        ;

        $this->resolver1
            ->expects($this->never())
            ->method('resolveContent')
        ;

        $this->resolver2
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(true)
        ;

        $this->resolver2
            ->expects($this->once())
            ->method('resolveContent')
            ->with($content)
            ->willReturn(new Route('/foobar'))
        ;

        $url = $this->generator->generate(
            ContentRoute::ROUTE_NAME,
            [ContentRoute::CONTENT_PARAMETER => $content]
        );

        $this->assertSame('/foobar', $url);
    }

    public function testThrowsExceptionIfNoResolverSupportsTheContent(): void
    {
        $content = (object) ['foo' => 'bar'];

        $this->resolver1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(false)
        ;

        $this->resolver2
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(false)
        ;

        $this->expectException(ContentRouteNotFoundException::class);

        $this->generator->generate(
            ContentRoute::ROUTE_NAME,
            [ContentRoute::CONTENT_PARAMETER => $content]
        );
    }

    public function testGeneratesTheContentRouteIfNoPageProviderIsRegisteredForType(): void
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

        $route = new ContentRoute($page, $content);

        $this->resolver1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(true)
        ;

        $this->resolver1
            ->expects($this->once())
            ->method('resolveContent')
            ->with($content)
            ->willReturn($route)
        ;

        $this->resolver2
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->providers
            ->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn(false)
        ;

        $this->providers
            ->expects($this->never())
            ->method('get')
        ;

        $url = $this->generator->generate(
            ContentRoute::ROUTE_NAME,
            [ContentRoute::CONTENT_PARAMETER => $content],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/some-language/foobar.html', $url);
    }

    public function testGetsRouteFromPageProviderForContentRoute(): void
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

        $route = new ContentRoute($page, $content);

        $provider = $this->createMock(PageProviderInterface::class);
        $provider
            ->expects($this->once())
            ->method('getRouteForPage')
            ->with($page, $content)
            ->willReturn($route)
        ;

        $this->resolver1
            ->expects($this->once())
            ->method('supportsContent')
            ->with($content)
            ->willReturn(true)
        ;

        $this->resolver1
            ->expects($this->once())
            ->method('resolveContent')
            ->with($content)
            ->willReturn($route)
        ;

        $this->resolver2
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->providers
            ->expects($this->once())
            ->method('has')
            ->with('foo')
            ->willReturn(true)
        ;

        $this->providers
            ->expects($this->once())
            ->method('get')
            ->with('foo')
            ->willReturn($provider)
        ;

        $url = $this->generator->generate(
            ContentRoute::ROUTE_NAME,
            [ContentRoute::CONTENT_PARAMETER => $content],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/some-language/foobar.html', $url);
    }
}
