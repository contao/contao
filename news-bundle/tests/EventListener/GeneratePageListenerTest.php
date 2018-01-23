<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\NewsBundle\EventListener\GeneratePageListener;
use Contao\NewsFeedModel;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;

class GeneratePageListenerTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new GeneratePageListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\NewsBundle\EventListener\GeneratePageListener', $listener);
    }

    public function testAddsTheNewsFeedLink(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['newsfeeds' => 'a:1:{i:0;i:3;}']);

        $properties = [
            'feedBase' => 'http://localhost/',
            'alias' => 'news',
            'format' => 'rss',
            'title' => 'Latest news',
        ];

        $newsFeedModel = $this->mockClassWithProperties(NewsFeedModel::class, $properties);
        $collection = new Collection([$newsFeedModel], 'tl_news_feeds');

        $adapters = [
            NewsFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => $collection]),
            Template::class => new Adapter(Template::class),
        ];

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertSame(
            ['<link type="application/rss+xml" rel="alternate" href="http://localhost/share/news.xml" title="Latest news">'],
            $GLOBALS['TL_HEAD']
        );
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoFeeds(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['newsfeeds' => '']);

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoModels(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['newsfeeds' => 'a:1:{i:0;i:3;}']);

        $adapters = [
            NewsFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => null]),
        ];

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }
}
