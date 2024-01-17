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
use Contao\CoreBundle\Routing\Content\PageResolver;
use Contao\CoreBundle\Routing\Content\StringUrl;
use Contao\CoreBundle\Tests\TestCase;
use Contao\PageModel;

class PageResolverTest extends TestCase
{
    public function testAbstainsIfContentIsNotAPageModel(): void
    {
        $content = $this->mockClassWithProperties(ArticleModel::class);

        $resolver = new PageResolver($this->mockContaoFramework());
        $result = $resolver->resolve($content);

        $this->assertNull($result);
    }

    public function testAbstainsIfContentPageModelIsNotRedirectOrForward(): void
    {
        $resolver = new PageResolver($this->mockContaoFramework());

        $content = $this->mockClassWithProperties(PageModel::class, ['type' => 'regular']);
        $result = $resolver->resolve($content);
        $this->assertNull($result);

        $content = $this->mockClassWithProperties(PageModel::class, ['type' => 'root']);
        $result = $resolver->resolve($content);
        $this->assertNull($result);

        $content = $this->mockClassWithProperties(PageModel::class, ['type' => 'foobar']);
        $result = $resolver->resolve($content);
        $this->assertNull($result);
    }

    public function testReturnsRedirectUrl(): void
    {
        $content = $this->mockClassWithProperties(PageModel::class, ['type' => 'redirect', 'url' => 'https://example.com/']);

        $resolver = new PageResolver($this->mockContaoFramework());
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertInstanceOf(StringUrl::class, $result->content);
        $this->assertSame('https://example.com/', $result->content->value);
    }

    public function testRedirectsToJumpToOfForwardPage(): void
    {
        $content = $this->mockClassWithProperties(PageModel::class, ['id' => 42, 'type' => 'forward', 'jumpTo' => 43]);
        $jumpTo = $this->mockClassWithProperties(PageModel::class, ['id' => 43]);

        $pageAdapter = $this->mockAdapter(['findPublishedById']);
        $pageAdapter
            ->expects($this->once())
            ->method('findPublishedById')
            ->with(43)
            ->willReturn($jumpTo)
        ;

        $resolver = new PageResolver($this->mockContaoFramework([PageModel::class => $pageAdapter]));
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($jumpTo, $result->content);
    }

    public function testRedirectsToFirstSubpageForwardPage(): void
    {
        $content = $this->mockClassWithProperties(PageModel::class, ['id' => 42, 'type' => 'forward', 'jumpTo' => 0]);
        $jumpTo = $this->mockClassWithProperties(PageModel::class);

        $pageAdapter = $this->mockAdapter(['findFirstPublishedRegularByPid']);
        $pageAdapter
            ->expects($this->once())
            ->method('findFirstPublishedRegularByPid')
            ->with(42)
            ->willReturn($jumpTo)
        ;

        $resolver = new PageResolver($this->mockContaoFramework([PageModel::class => $pageAdapter]));
        $result = $resolver->resolve($content);

        $this->assertTrue($result->isRedirect());
        $this->assertSame($jumpTo, $result->content);
    }
}
