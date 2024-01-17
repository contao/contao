<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Routing;

use Contao\ArticleModel;
use Contao\CoreBundle\Routing\Content\StringUrl;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\Routing\NewsResolver;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class NewsResolverTest extends ContaoTestCase
{
    public function testResolveNewsWithExternalSource(): void
    {
        $content = $this->mockClassWithProperties(NewsModel::class, ['source' => 'external', 'url' => 'foobar']);

        $resolver = new NewsResolver($this->mockContaoFramework());
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertInstanceOf(StringUrl::class, $result->content);
        $this->assertSame('foobar', $result->content->value);
    }

    public function testResolveNewsWithInternalSource(): void
    {
        $jumpTo = $this->mockClassWithProperties(PageModel::class);
        $content = $this->mockClassWithProperties(NewsModel::class, ['source' => 'internal', 'jumpTo' => 42]);

        $pageAdapter = $this->mockAdapter(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($jumpTo)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $resolver = new NewsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($jumpTo, $result->content);
    }

    public function testResolveNewsWithArticleSource(): void
    {
        $article = $this->mockClassWithProperties(ArticleModel::class);
        $content = $this->mockClassWithProperties(NewsModel::class, ['source' => 'article', 'articleId' => 42]);

        $articleAdapter = $this->mockAdapter(['findPublishedById']);
        $articleAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($article)
        ;

        $framework = $this->mockContaoFramework([ArticleModel::class => $articleAdapter]);

        $resolver = new NewsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($article, $result->content);
    }

    public function testResolveNewsWithoutSource(): void
    {
        $target = $this->mockClassWithProperties(PageModel::class);
        $newsArchive = $this->mockClassWithProperties(NewsArchiveModel::class, ['jumpTo' => 42]);

        $content = $this->mockClassWithProperties(NewsModel::class, ['source' => '']);
        $content
            ->expects($this->once())
            ->method('getRelated')
            ->with('pid')
            ->willReturn($newsArchive)
        ;

        $pageAdapter = $this->mockAdapter(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(42)
            ->willReturn($target)
        ;

        $framework = $this->mockContaoFramework([PageModel::class => $pageAdapter]);

        $resolver = new NewsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertFalse($result->isRedirect());
        $this->assertSame($target, $result->content);
    }

    /**
     * @dataProvider getParametersForContentProvider
     */
    public function testGetParametersForContent(object $content, array $expected): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $resolver = new NewsResolver($this->mockContaoFramework());

        $this->assertSame($expected, $resolver->getParametersForContent($content, $pageModel));
    }

    public function getParametersForContentProvider(): \Generator
    {
        yield 'Uses the news alias' => [
            $this->mockClassWithProperties(NewsModel::class, ['id' => 42, 'alias' => 'foobar']),
            ['parameters' => '/foobar'],
        ];

        yield 'Uses news ID if alias is empty' => [
            $this->mockClassWithProperties(NewsModel::class, ['id' => 42, 'alias' => '']),
            ['parameters' => '/42'],
        ];

        yield 'Only supports NewsModel' => [
            $this->mockClassWithProperties(PageModel::class),
            [],
        ];
    }
}
