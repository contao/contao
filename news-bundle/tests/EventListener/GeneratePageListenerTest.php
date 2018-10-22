<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\NewsBundle\EventListener\GeneratePageListener;
use Contao\NewsFeedModel;
use Contao\PageModel;
use PHPUnit\Framework\TestCase;

/**
 * Tests the GeneratePageListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class GeneratePageListenerTest extends TestCase
{
    /**
     * Tests that the listener returns a replacement string for a calendar feed.
     */
    public function testAddsTheNewsFeedLink()
    {
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                if ('newsfeeds' === $key) {
                    return 'a:1:{i:0;i:3;}';
                }

                return null;
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertSame(
            [
                '<link type="application/rss+xml" rel="alternate" href="http://localhost/share/news.xml" title="Latest news">',
            ],
            $GLOBALS['TL_HEAD']
        );
    }

    /**
     * Tests that the listener returns if there are no feeds.
     */
    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoFeeds()
    {
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                if ('newsfeeds' === $key) {
                    return '';
                }

                return null;
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    /**
     * Tests that the listener returns if there are no models.
     */
    public function testDoesNotAddTheNewsFeedLinkIfThereAreNoModels()
    {
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                if ('newsfeeds' === $key) {
                    return 'a:1:{i:0;i:3;}';
                }

                return null;
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework(true));
        $listener->onGeneratePage($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param bool $noModels
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($noModels = false)
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $feedModel = $this->createMock(NewsFeedModel::class);

        $feedModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'feedBase':
                        return 'http://localhost/';

                    case 'alias':
                        return 'news';

                    case 'format':
                        return 'rss';

                    case 'title':
                        return 'Latest news';

                    default:
                        return null;
                }
            })
        ;

        $newsFeedModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIds'])
            ->getMock()
        ;

        $newsFeedModelAdapter
            ->method('findByIds')
            ->willReturn($noModels ? null : new Collection([$feedModel], 'tl_news_feeds'))
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($newsFeedModelAdapter) {
                switch ($key) {
                    case 'Contao\NewsFeedModel':
                        return $newsFeedModelAdapter;

                    case 'Contao\Template':
                        return new Adapter('Contao\Template');

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
