<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CalendarBundle\EventListener\GeneratePageListener;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\Framework\Adapter;
use Contao\LayoutModel;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\Template;
use Contao\TestCase\ContaoTestCase;

class GeneratePageListenerTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new GeneratePageListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\GeneratePageListener', $listener);
    }

    public function testAddsTheCalendarFeedLink(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['calendarfeeds' => 'a:1:{i:0;i:3;}']);

        $properties = [
            'feedBase' => 'http://localhost/',
            'alias' => 'events',
            'format' => 'rss',
            'title' => 'Upcoming events',
        ];

        $calendarFeedModel = $this->mockClassWithProperties(CalendarFeedModel::class, $properties);
        $collection = new Collection([$calendarFeedModel], 'tl_calendar_feeds');

        $adapters = [
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => $collection]),
            Template::class => new Adapter(Template::class),
        ];

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertSame(
            ['<link type="application/rss+xml" rel="alternate" href="http://localhost/share/events.xml" title="Upcoming events">'],
            $GLOBALS['TL_HEAD']
        );
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoFeeds(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['calendarfeeds' => '']);

        $listener = new GeneratePageListener($this->mockContaoFramework());
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }

    public function testDoesNotAddTheCalendarFeedLinkIfThereAreNoModels(): void
    {
        $GLOBALS['TL_HEAD'] = [];

        $pageModel = $this->createMock(PageModel::class);
        $layoutModel = $this->mockClassWithProperties(LayoutModel::class, ['calendarfeeds' => 'a:1:{i:0;i:3;}']);

        $adapters = [
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByIds' => null]),
        ];

        $listener = new GeneratePageListener($this->mockContaoFramework($adapters));
        $listener->onGeneratePage($pageModel, $layoutModel);

        $this->assertEmpty($GLOBALS['TL_HEAD']);
    }
}
