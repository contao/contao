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
use Contao\Model;
use Contao\NewsArchiveModel;
use Contao\NewsBundle\Routing\NewsResolver;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NewsResolverTest extends ContaoTestCase
{
    public function testResolveNewsWithExternalSource(): void
    {
        $content = $this->createClassWithPropertiesStub(NewsModel::class, ['source' => 'external', 'url' => 'foobar']);

        $resolver = new NewsResolver($this->createContaoFrameworkStub());
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertInstanceOf(StringUrl::class, $result->content);
        $this->assertSame('foobar', $result->content->value);
    }

    public function testResolveNewsWithInternalSource(): void
    {
        $jumpTo = $this->createClassWithPropertiesStub(PageModel::class);
        $content = $this->createClassWithPropertiesStub(NewsModel::class, ['source' => 'internal', 'jumpTo' => 42]);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($jumpTo)
        ;

        $framework = $this->createContaoFrameworkStub([PageModel::class => $pageAdapter]);

        $resolver = new NewsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($jumpTo, $result->content);
    }

    public function testResolveNewsWithArticleSource(): void
    {
        $article = $this->createClassWithPropertiesStub(ArticleModel::class);
        $content = $this->createClassWithPropertiesStub(NewsModel::class, ['source' => 'article', 'articleId' => 42]);

        $articleAdapter = $this->createAdapterMock(['findById']);
        $articleAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($article)
        ;

        $framework = $this->createContaoFrameworkStub([ArticleModel::class => $articleAdapter]);

        $resolver = new NewsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($article, $result->content);
    }

    public function testResolveNewsWithoutSource(): void
    {
        $target = $this->createClassWithPropertiesStub(PageModel::class);
        $newsArchive = $this->createClassWithPropertiesStub(NewsArchiveModel::class, ['jumpTo' => 42]);

        $content = $this->createClassWithPropertiesStub(NewsModel::class, ['source' => '']);

        $pageAdapter = $this->createAdapterMock(['findById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($target)
        ;

        $framework = $this->createContaoFrameworkStub([
            PageModel::class => $pageAdapter,
            NewsArchiveModel::class => $this->createConfiguredAdapterStub(['findById' => $newsArchive]),
        ]);

        $resolver = new NewsResolver($framework);
        $result = $resolver->resolve($content);

        $this->assertFalse($result->isRedirect());
        $this->assertSame($target, $result->content);
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('getParametersForContentProvider')]
    public function testGetParametersForContent(string $class, array $properties, array $expected): void
    {
        $content = $this->createClassWithPropertiesStub($class, $properties);
        $pageModel = $this->createClassWithPropertiesStub(PageModel::class);
        $resolver = new NewsResolver($this->createContaoFrameworkStub());

        $this->assertSame($expected, $resolver->getParametersForContent($content, $pageModel));
    }

    public static function getParametersForContentProvider(): iterable
    {
        yield 'Uses the news alias' => [
            NewsModel::class,
            ['id' => 42, 'alias' => 'foobar'],
            ['parameters' => '/foobar'],
        ];

        yield 'Uses news ID if alias is empty' => [
            NewsModel::class,
            ['id' => 42, 'alias' => ''],
            ['parameters' => '/42'],
        ];

        yield 'Only supports NewsModel' => [
            PageModel::class,
            [],
            [],
        ];
    }
}
