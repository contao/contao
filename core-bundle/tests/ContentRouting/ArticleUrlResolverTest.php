<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\ContentRouting;

use Contao\ArticleModel;
use Contao\CoreBundle\ContentRouting\ArticleUrlResolver;
use Contao\CoreBundle\ContentRouting\ContentRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class ArticleUrlResolverTest extends TestCase
{
    /**
     * @var ArticleUrlResolver
     */
    private $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ArticleUrlResolver();
    }

    public function testSupportsArticles(): void
    {
        $this->assertTrue($this->resolver->supportsContent($this->mockArticle()));
        $this->assertFalse($this->resolver->supportsContent($this->mockClassWithProperties(PageModel::class)));
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

        /** @var ContentRoute $route */
        $route = $this->resolver->resolveContent($article);

        $this->assertInstanceOf(ContentRoute::class, $route);
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

        /** @var ContentRoute $route */
        $route = $this->resolver->resolveContent($article);

        $this->assertInstanceOf(ContentRoute::class, $route);
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

        $this->resolver->resolveContent($article);
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
