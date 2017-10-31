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

use Contao\CalendarBundle\EventListener\InsertTagsListener;
use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\Events;
use Contao\TestCase\ContaoTestCase;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\InsertTagsListener', $listener);
    }

    public function testReplacesTheCalendarFeedTag(): void
    {
        $properties = [
            'feedBase' => 'http://localhost/',
            'alias' => 'events',
        ];

        $feedModel = $this->mockClassWithProperties(CalendarFeedModel::class, $properties);

        $adapters = [
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByPk' => $feedModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $listener = new InsertTagsListener($framework);

        $this->assertSame('http://localhost/share/events.xml', $listener->onReplaceInsertTags('calendar_feed::2'));
    }

    public function testReplacesTheEventTags(): void
    {
        $properties = [
            'title' => 'The "foobar" event',
            'teaser' => '<p>The annual foobar event.</p>',
        ];

        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class, $properties);

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $eventModel]),
            Events::class => $this->mockConfiguredAdapter(['generateEventUrl' => 'events/the-foobar-event.html']),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertSame(
            'events/the-foobar-event.html',
            $listener->onReplaceInsertTags('event_url::2')
        );

        $this->assertSame(
            'The &quot;foobar&quot; event',
            $listener->onReplaceInsertTags('event_title::2')
        );

        $this->assertSame(
            '<p>The annual foobar event.</p>',
            $listener->onReplaceInsertTags('event_teaser::2')
        );
    }

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame('', $listener->onReplaceInsertTags('calendar_feed::3'));
        $this->assertSame('', $listener->onReplaceInsertTags('event_url::3'));
    }
}
