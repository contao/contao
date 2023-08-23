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

use Contao\CalendarBundle\EventListener\InsertTagsListener;
use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\Events;
use Contao\TestCase\ContaoTestCase;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testReplacesTheCalendarFeedTag(): void
    {
        $feedModel = $this->mockClassWithProperties(CalendarFeedModel::class);
        $feedModel->feedBase = 'http://localhost/';
        $feedModel->alias = 'events';

        $adapters = [
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByPk' => $feedModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $listener = new InsertTagsListener($framework);
        $url = $listener('calendar_feed::2', false, null, []);

        $this->assertSame('http://localhost/share/events.xml', $url);
    }

    public function testReplacesTheEventTags(): void
    {
        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class);
        $eventModel->title = 'The "foobar" event';
        $eventModel->teaser = '<p>The annual foobar event.</p>';

        $events = $this->mockAdapter(['generateEventUrl']);
        $events
            ->method('generateEventUrl')
            ->willReturnCallback(
                static function (CalendarEventsModel $model, bool $absolute): string {
                    if ($absolute) {
                        return 'http://domain.tld/events/the-foobar-event.html';
                    }

                    return 'events/the-foobar-event.html';
                },
            )
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $eventModel]),
            Events::class => $events,
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener('event::2', false, null, []),
        );

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">The "foobar" event</a>',
            $listener('event::2::blank', false, null, []),
        );

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">',
            $listener('event_open::2', false, null, []),
        );

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">',
            $listener('event_open::2::blank', false, null, []),
        );

        $this->assertSame(
            '<a href="http://domain.tld/events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">',
            $listener('event_open::2::blank::absolute', false, null, []),
        );

        $this->assertSame(
            '<a href="http://domain.tld/events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">',
            $listener('event_open::2::absolute::blank', false, null, []),
        );

        $this->assertSame(
            'events/the-foobar-event.html',
            $listener('event_url::2', false, null, []),
        );

        $this->assertSame(
            'http://domain.tld/events/the-foobar-event.html',
            $listener('event_url::2', false, null, ['absolute']),
        );

        $this->assertSame(
            'http://domain.tld/events/the-foobar-event.html',
            $listener('event_url::2::absolute', false, null, []),
        );

        $this->assertSame(
            'http://domain.tld/events/the-foobar-event.html',
            $listener('event_url::2::blank::absolute', false, null, []),
        );

        $this->assertSame(
            'The &quot;foobar&quot; event',
            $listener('event_title::2', false, null, []),
        );

        $this->assertSame(
            '<p>The annual foobar event.</p>',
            $listener('event_teaser::2', false, null, []),
        );
    }

    public function testHandlesEmptyUrls(): void
    {
        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class);
        $eventModel->title = 'The "foobar" event';
        $eventModel->teaser = '<p>The annual foobar event.</p>';

        $events = $this->mockAdapter(['generateEventUrl']);
        $events
            ->method('generateEventUrl')
            ->willReturn('')
        ;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $eventModel]),
            Events::class => $events,
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame(
            '<a href="./" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener('event::2', false, null, []),
        );

        $this->assertSame(
            '<a href="./" title="The &quot;foobar&quot; event">',
            $listener('event_open::2', false, null, []),
        );

        $this->assertSame(
            './',
            $listener('event_url::2', false, null, []),
        );
    }

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener('link_url::2', false, null, []));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters));

        $this->assertSame('', $listener('calendar_feed::3', false, null, []));
        $this->assertSame('', $listener('event_url::3', false, null, []));
    }
}
