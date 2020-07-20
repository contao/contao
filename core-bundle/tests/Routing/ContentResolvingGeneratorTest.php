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

use Contao\CoreBundle\Routing\ContentResolvingGenerator;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\RouteFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ContentResolvingGeneratorTest extends TestCase
{
    /**
     * @var RouteFactory&MockObject
     */
    private $routeFactory;

    /**
     * @var ContentResolvingGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $this->routeFactory = $this->createMock(RouteFactory::class);
        $this->generator = new ContentResolvingGenerator($this->routeFactory);
    }

    public function testThrowsExceptionIfRouteNameIsNotSupported(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Route name is not "contao_routing_object"');

        $this->generator->generate('foo');
    }

    public function testThrowsExceptionIfContentParameterIsNotSet(): void
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Missing parameter "_content" for content route (contao_routing_object).');

        $this->generator->generate(PageRoute::ROUTE_NAME);
    }

    public function testGeneratesTheContentRoute(): void
    {
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

        $content = (object) ['foo' => 'bar'];
        $route = new PageRoute($page);

        $this->routeFactory
            ->expects($this->once())
            ->method('createRouteForContent')
            ->with($content)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/some-language/foobar.html', $url);
    }

    public function testReplacesTheRoutePathForTheIndexRouteWithoutParameters(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, [
            'id' => 17,
            'alias' => 'index',
            'type' => 'regular',
            'domain' => 'www.example.com',
            'rootUseSSL' => true,
            'urlPrefix' => 'en',
            'urlSuffix' => '.html',
        ]);

        $content = (object) ['foo' => 'bar'];
        $route = new PageRoute($page);

        $this->routeFactory
            ->expects($this->once())
            ->method('createRouteForContent')
            ->with($content)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/en/', $url);
    }

    public function testReplacesTheRoutePathForTheIndexRouteWithParameters(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, [
            'id' => 17,
            'alias' => 'index',
            'type' => 'regular',
            'domain' => 'www.example.com',
            'rootUseSSL' => true,
            'urlPrefix' => 'en',
            'urlSuffix' => '.html',
        ]);

        $content = (object) ['foo' => 'bar'];
        $route = new PageRoute($page, '{parameters}', ['parameters' => null]);

        $this->routeFactory
            ->expects($this->once())
            ->method('createRouteForContent')
            ->with($content)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            PageRoute::ROUTE_NAME,
            [PageRoute::CONTENT_PARAMETER => $content],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/en/', $url);
    }
}
