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
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Routing\Content\ArticleResolver;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;

class ArticleResolverTest extends TestCase
{
    public function testAbstainsIfContentIsNotAnArticleModel(): void
    {
        $content = $this->mockClassWithProperties(PageModel::class);

        $resolver = new ArticleResolver($this->mockContaoFramework());
        $result = $resolver->resolve($content);

        $this->assertNull($result);
    }

    public function testResolvesArticleModel(): void
    {
        $content = $this->mockClassWithProperties(ArticleModel::class, ['pid' => 42]);
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['id' => 42]);

        $pageAdapter = $this->mockAdapter(['findWithDetails']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(42)
            ->willReturn($pageModel)
        ;

        $resolver = new ArticleResolver($this->mockContaoFramework([PageModel::class => $pageAdapter]));
        $result = $resolver->resolve($content);

        $this->assertFalse($result->isRedirect());
        $this->assertSame($pageModel, $result->content);
    }

    public function testThrowsExceptionIfPageOfArticleIsNotFound(): void
    {
        $content = $this->mockClassWithProperties(ArticleModel::class, ['pid' => 42]);

        $pageAdapter = $this->mockAdapter(['findWithDetails']);
        $pageAdapter
            ->expects($this->once())
            ->method('findWithDetails')
            ->with(42)
            ->willReturn(null)
        ;

        $resolver = new ArticleResolver($this->mockContaoFramework([PageModel::class => $pageAdapter]));

        $this->expectException(ForwardPageNotFoundException::class);

        $resolver->resolve($content);
    }
}
