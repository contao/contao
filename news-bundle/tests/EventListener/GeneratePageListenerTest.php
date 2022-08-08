<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\Environment;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\NewsBundle\EventListener\GeneratePageListener;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;

class GeneratePageListenerTest extends ContaoTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TL_HEAD'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_CONFIG'], $GLOBALS['TL_HEAD']);

        parent::tearDown();
    }

    public function testAddsTheNewsFeedLink(): void
    {
        $newsFeedModel = $this->createMock(PageModel::class);
        $newsFeedModel
            ->method('__get')
            ->willReturnMap([
                ['id', 42],
                ['type', 'news_feed'],
                ['alias', 'news'],
                ['title', 'Latest news'],
                ['feedFormat', 'rss'],
            ])
        ;

        $newsFeedModel
            ->expects($this->once())
            ->method('getAbsoluteUrl')
            ->willReturn('http://localhost/news.xml')
        ;

        $collection = new Collection([$newsFeedModel], 'tl_page');

        $adapters = [
            Environment::class => $this->mockAdapter(['get']),
            PageModel::class => $this->mockConfiguredAdapter(['findMultipleByIds' => $collection]),
            Template::class => new Adapter(Template::class),
        ];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->newsfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertSame(
            ['<link type="application/rss+xml" rel="alternate" href="http://localhost/news.xml" title="Latest news">'],
            $GLOBALS['TL_HEAD']
        );
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoFeeds(): void
    {
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->newsfeeds = '';

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD'] ?? null);
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoValidModels(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['type' => 'regular']);
        $collection = new Collection([$pageModel], 'tl_page');

        $adapters = [
            PageModel::class => $this->mockConfiguredAdapter(['findMultipleByIds' => $collection]),
        ];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->newsfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD'] ?? null);
    }
}
