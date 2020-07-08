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
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class ArticleRouteProviderTest extends TestCase
{
    /**
     * @var ArticleRouteProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new ArticleRouteProvider();
    }

    public function testSupportsArticles(): void
    {
        $this->assertTrue($this->provider->supportsContent($this->mockArticle()));
        $this->assertFalse($this->provider->supportsContent($this->mockClassWithProperties(PageModel::class)));
    }

    public function testCreatesParameterdContentRouteForArticle(): void
    {
        $page = $this->mockPage();
        $article = $this->mockArticle(['alias' => 'foobar']);

        $article
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($page)
        ;

        /** @var PageRoute $route */
        $route = $this->provider->resolveContent($article);

        $this->assertInstanceOf(PageRoute::class, $route);
        $this->assertSame($page, $route->getPage());
        $this->assertSame('/foo/bar{parameters}.baz', $route->getPath());
        $this->assertSame('/articles/foobar', $route->getDefault('parameters'));
    }

    public function testCreatesParameterdContentRouteWithIdIfArticleHasNoAlias(): void
    {
        $page = $this->mockPage();
        $article = $this->mockArticle(['id' => 17, 'alias' => '']);

        $article
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($page)
        ;

        /** @var PageRoute $route */
        $route = $this->provider->resolveContent($article);

        $this->assertInstanceOf(PageRoute::class, $route);
        $this->assertSame($page, $route->getPage());
        $this->assertSame('/foo/bar{parameters}.baz', $route->getPath());
        $this->assertSame('/articles/17', $route->getDefault('parameters'));
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

        $this->provider->resolveContent($article);
    }

    /**
     * @return ArticleModel&MockObject $article
     */
    private function mockArticle(array $parameters = []): ArticleModel
    {
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
     * @return PageModel&MockObject $page
     */
    private function mockPage(array $properties = []): PageModel
    {
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
