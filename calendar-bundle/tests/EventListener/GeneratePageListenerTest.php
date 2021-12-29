<?php

declare(strict_types=1);

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
use Contao\Environment;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;

class GeneratePageListenerTest extends ContaoTestCase
{
    public function testAddsTheCalendarFeedLink(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $calendarFeedModel = $this->mockClassWithProperties(CalendarFeedModel::class);
        $calendarFeedModel->feedBase = 'http://localhost/';
        $calendarFeedModel->alias = 'events';
        $calendarFeedModel->format = 'rss';
        $calendarFeedModel->title = 'Upcoming events';

        $collection = new Collection([$calendarFeedModel], 'tl_calendar_feeds');

        $adapters = [
            Environment::class => $this->mockAdapter(['get']),
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => $collection]),
            Template::class => new Adapter(Template::class),
        ];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->calendarfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertSame(
            ['<link type="application/rss+xml" rel="alternate" href="http://localhost/share/events.xml" title="Upcoming events">'],
            $GLOBALS['TL_HEAD']
        );
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoFeeds(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->calendarfeeds = '';

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoModels(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $adapters = [
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => null]),
        ];

        $layoutModel = $this->mockClassWithProperties(LayoutModel::class);
        $layoutModel->calendarfeeds = 'a:1:{i:0;i:3;}';

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener($this->createMock(PageModel::class), $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }
}
