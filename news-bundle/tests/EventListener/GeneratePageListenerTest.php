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
use Contao\NewsFeedModel;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class GeneratePageListenerTest extends ContaoTestCase
{
    public function testAddsTheNewsFeedLink(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        /** @var NewsFeedModel&MockObject $newsFeedModel */
        $newsFeedModel = $this->mockClassWithProperties(NewsFeedModel::class);
        $newsFeedModel->feedBase = 'http://localhost/';
        $newsFeedModel->alias = 'news';
        $newsFeedModel->format = 'rss';
        $newsFeedModel->title = 'Latest news';

        $collection = new Collection([$newsFeedModel], 'tl_news_feeds');

        $adapters = [
            Environment::class => $this->mockAdapter(['get']),
            NewsFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => $collection]),
            Template::class => new Adapter(Template::class),
        ];

        /** @var LayoutModel&MockObject $layoutModel */
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->newsfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertSame(
            ['<link type="application/rss+xml" rel="alternate" href="http://localhost/share/news.xml" title="Latest news">'],
            $GLOBALS['TL_HEAD']
        );
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoFeeds(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        /** @var LayoutModel&MockObject $layoutModel */
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->newsfeeds = '';

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoModels(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $adapters = [
            NewsFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => null]),
        ];

        /** @var LayoutModel&MockObject $layoutModel */
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->newsfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }
}
