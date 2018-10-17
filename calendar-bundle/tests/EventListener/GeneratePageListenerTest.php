<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CalendarBundle\EventListener\GeneratePageListener;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\Template;
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
    public function testAddsTheCalendarFeedLink()
    {
        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'calendarfeeds':
                        return 'a:1:{i:0;i:3;}';

                    default:
                        return null;
                }
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertSame(
            [
                '<link type="application/rss+xml" rel="alternate" href="http://localhost/share/events.xml" title="Upcoming events">',
            ],
            $GLOBALS['TL_HEAD']
        );
    }

    /**
     * Tests that the listener returns if there are no feeds.
     */
    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoFeeds()
    {
        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'calendarfeeds':
                        return '';

                    default:
                        return null;
                }
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    /**
     * Tests that the listener returns if there are no models.
     */
    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoModels()
    {
        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->createMock(LayoutModel::class);

        $layoutModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'calendarfeeds':
                        return 'a:1:{i:0;i:3;}';

                    default:
                        return null;
                }
            })
        ;

        $GLOBALS['TL_HEAD'] = [];

        $listener = new GeneratePageListener($this->mockContaoFramework(true));
        $listener->onGeneratePage($pageModel, $layoutModel);

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

        $feedModel = $this->createMock(CalendarFeedModel::class);

        $feedModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'feedBase':
                        return 'http://localhost/';

                    case 'alias':
                        return 'events';

                    case 'format':
                        return 'rss';

                    case 'title':
                        return 'Upcoming events';

                    default:
                        return null;
                }
            })
        ;

        $calendarFeedModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIds'])
            ->getMock()
        ;

        $calendarFeedModelAdapter
            ->method('findByIds')
            ->willReturn($noModels ? null : new Collection([$feedModel], 'tl_calendar_feeds'))
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($calendarFeedModelAdapter) {
                switch ($key) {
                    case CalendarFeedModel::class:
                        return $calendarFeedModelAdapter;

                    case Template::class:
                        return new Adapter(Template::class);

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
