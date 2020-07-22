<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\Content;

use Contao\ArticleModel;
use Contao\CoreBundle\Routing\Content\ArticleRouteProvider;
use Contao\CoreBundle\Routing\RouteFactory;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;

class ArticleRouteProviderTest extends TestCase
{
    /**
     * @var RouteFactory|MockObject
     */
    private $routeFactory;

    /**
     * @var ArticleRouteProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->routeFactory = $this->createMock(RouteFactory::class);
        $this->provider = new ArticleRouteProvider($this->routeFactory);
    }

    public function testSupportsArticles(): void
    {
        $this->assertTrue($this->provider->supportsContent($this->mockArticle()));
        $this->assertFalse($this->provider->supportsContent($this->mockClassWithProperties(PageModel::class)));
    }

    public function testCreatesParameteredContentRouteForArticle(): void
    {
        $page = $this->mockPage();
        $route = new Route('/');

        $article = $this->mockArticle(['alias' => 'foobar']);
        $article
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($page)
        ;

        $this->routeFactory
            ->expects($this->once())
            ->method('createRouteForPage')
            ->with($page, '/articles/foobar', $article)
            ->willReturn($route)
        ;

        $this->assertSame($route, $this->provider->getRouteForContent($article));
    }

    public function testCreatesParameteredContentRouteWithIdIfArticleHasNoAlias(): void
    {
        $page = $this->mockPage();
        $route = new Route('/');

        $article = $this->mockArticle(['id' => 17, 'alias' => '']);
        $article
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($page)
        ;

        $this->routeFactory
            ->expects($this->once())
            ->method('createRouteForPage')
            ->with($page, '/articles/17', $article)
            ->willReturn($route)
        ;

        $this->assertSame($route, $this->provider->getRouteForContent($article));
    }

    public function testThrowsExceptionIfPageIsNotFound(): void
    {
        $article = $this->mockArticle();
        $article
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn(null)
        ;

        $this->expectException(RouteNotFoundException::class);

        $this->provider->getRouteForContent($article);
    }

    /**
     * @return ArticleModel&MockObject
     */
    private function mockArticle(array $parameters = []): ArticleModel
    {
        /** @var ArticleModel&MockObject */
        return $this->mockClassWithProperties(
            ArticleModel::class,
            array_merge(
                [
                    'id' => 5,
                    'pid' => 1,
                    'alias' => 'foo',
                ],
                $parameters
            )
        );
    }

    /**
     * @return PageModel&MockObject
     */
    private function mockPage(array $properties = []): PageModel
    {
        /** @var PageModel&MockObject */
        return $this->mockClassWithProperties(
            PageModel::class,
            array_merge(
                [
                    'id' => 17,
                    'alias' => 'bar',
                    'domain' => 'www.example.com',
                    'rootLanguage' => 'xy',
                    'rootUseSSL' => true,
                    'urlPrefix' => 'foo',
                    'urlSuffix' => '.baz',
                ],
                $properties
            )
        );
    }
}
