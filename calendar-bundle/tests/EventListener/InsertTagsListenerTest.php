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
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InsertTagsListenerTest extends ContaoTestCase
{
    public function testReplacesTheCalendarFeedTag(): void
    {
        $feedModel = $this->mockClassWithProperties(CalendarFeedModel::class);
        $feedModel->feedBase = 'http://localhost/';
        $feedModel->alias = 'events';

        $adapters = [
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findById' => $feedModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);

        $listener = new InsertTagsListener($framework, $urlGenerator);
        $url = $listener('calendar_feed::2', false, null, []);

        $this->assertSame('http://localhost/share/events.xml', $url);
    }

    public function testReplacesTheEventTags(): void
    {
        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class);
        $eventModel->title = 'The "foobar" event';
        $eventModel->teaser = '<p>The annual foobar event.</p>';

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => $eventModel]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->exactly(10))
            ->method('generate')
            ->withConsecutive(
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_PATH],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
                [$eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL],
            )
            ->willReturnOnConsecutiveCalls(
                'events/the-foobar-event.html',
                'events/the-foobar-event.html',
                'events/the-foobar-event.html',
                'events/the-foobar-event.html',
                'http://domain.tld/events/the-foobar-event.html',
                'http://domain.tld/events/the-foobar-event.html',
                'events/the-foobar-event.html',
                'http://domain.tld/events/the-foobar-event.html',
                'http://domain.tld/events/the-foobar-event.html',
                'http://domain.tld/events/the-foobar-event.html',
            )
        ;

        $listener = new InsertTagsListener($this->mockContaoFramework($adapters), $urlGenerator);

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

    public function testReturnsFalseIfTheTagIsUnknown(): void
    {
        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $listener = new InsertTagsListener($this->mockContaoFramework(), $urlGenerator);

        $this->assertFalse($listener('link_url::2', false, null, []));
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findById' => null]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $listener = new InsertTagsListener($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame('', $listener('calendar_feed::3', false, null, []));
        $this->assertSame('', $listener('event_url::3', false, null, []));
    }
}
