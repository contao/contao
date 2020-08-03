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

use Contao\CoreBundle\Exception\RouteParametersException;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\PageUrlGenerator;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageUrlGeneratorTest extends TestCase
{
    /**
     * @var PageRegistry&MockObject
     */
    private $pageRegistry;

    /**
     * @var PageUrlGenerator
     */
    private $generator;

    protected function setUp(): void
    {
        $provider = $this->createMock(RouteProviderInterface::class);

        $this->pageRegistry = $this->createMock(PageRegistry::class);
        $this->generator = new PageUrlGenerator($provider, $this->pageRegistry);
    }

    public function testGeneratesThePageRoute(): void
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

        $route = new PageRoute($page);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [RouteObjectInterface::CONTENT_OBJECT => $page],
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

        $route = new PageRoute($page, '/index{!parameters}', ['parameters' => ''], ['parameters' => '(/.+)?']);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [RouteObjectInterface::CONTENT_OBJECT => $page],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/en/', $url);
    }

    public function testReplacesTheRoutePathForTheIndexRouteWithEmptyParameters(): void
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

        $route = new PageRoute($page, '/index{!parameters}', ['parameters' => ''], ['parameters' => '(/.+)?']);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [RouteObjectInterface::CONTENT_OBJECT => $page, 'parameters' => null],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/en/', $url);
    }

    public function testDoesNotReplaceTheRoutePathForTheIndexRouteWithParameters(): void
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

        $route = new PageRoute($page, '/index{!parameters}', ['parameters' => ''], ['parameters' => '(/.+)?']);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [RouteObjectInterface::CONTENT_OBJECT => $page, 'parameters' => '/foobar'],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/en/index/foobar.html', $url);
    }

    public function testDoesNotReplaceTheRoutePathForTheIndexRouteWithDefaultParameters(): void
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

        $route = new PageRoute($page, '{foo}', ['foo' => 'foo'], ['foo' => '[a-z]+']);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $url = $this->generator->generate(
            RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
            [RouteObjectInterface::CONTENT_OBJECT => $page],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->assertSame('https://www.example.com/en/index/foo.html', $url);
    }

    public function testThrowsRouteParametersExceptionOnMissingParameters(): void
    {
        /** @var PageModel&MockObject $page */
        $page = $this->mockClassWithProperties(PageModel::class, [
            'id' => 17,
            'alias' => 'foo',
            'type' => 'regular',
            'domain' => 'www.example.com',
            'rootUseSSL' => true,
            'urlPrefix' => 'en',
            'urlSuffix' => '.html',
        ]);

        $route = new PageRoute($page, '{foo}', [], ['foo' => '[a-z]+']);

        $this->pageRegistry
            ->expects($this->once())
            ->method('getRoute')
            ->with($page)
            ->willReturn($route)
        ;

        $this->expectException(RouteParametersException::class);

        try {
            $this->generator->generate(
                RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                [RouteObjectInterface::CONTENT_OBJECT => $page, 'bar' => 'baz'],
                UrlGeneratorInterface::NETWORK_PATH
            );
        } catch (RouteParametersException $exception) {
            $this->assertSame($route, $exception->getRoute());
            $this->assertSame(['bar' => 'baz'], $exception->getParameters());
            $this->assertSame(UrlGeneratorInterface::NETWORK_PATH, $exception->getReferenceType());

            throw $exception;
        }
    }
}
