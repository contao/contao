<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\InsertTag;

use Contao\CalendarBundle\InsertTag\EventInsertTag;
use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\ResolvedParameters;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EventInsertTagTest extends ContaoTestCase
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

        $listener = new EventInsertTag($framework, $urlGenerator);
        $url = $listener(new ResolvedInsertTag('calendar_feed', new ResolvedParameters(['2']), []));

        $this->assertEquals(new InsertTagResult('http://localhost/share/events.xml', OutputType::url), $url);
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

        $listener = new EventInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener(new ResolvedInsertTag('event', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">The "foobar" event</a>',
            $listener(new ResolvedInsertTag('event', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">',
            $listener(new ResolvedInsertTag('event_open', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('event_open', new ResolvedParameters(['2', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('event_open', new ResolvedParameters(['2', 'blank', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            '<a href="http://domain.tld/events/the-foobar-event.html" title="The &quot;foobar&quot; event" target="_blank" rel="noreferrer noopener">',
            $listener(new ResolvedInsertTag('event_open', new ResolvedParameters(['2', 'absolute', 'blank']), []))->getValue(),
        );

        $this->assertSame(
            'events/the-foobar-event.html',
            $listener(new ResolvedInsertTag('event_url', new ResolvedParameters(['2']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/events/the-foobar-event.html',
            $listener(new ResolvedInsertTag('event_url', new ResolvedParameters(['2', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/events/the-foobar-event.html',
            $listener(new ResolvedInsertTag('event_url', new ResolvedParameters(['2', 'absolute']), []))->getValue(),
        );

        $this->assertSame(
            'http://domain.tld/events/the-foobar-event.html',
            $listener(new ResolvedInsertTag('event_url', new ResolvedParameters(['2', 'blank', 'absolute']), []))->getValue(),
        );

        $this->assertEquals(
            new InsertTagResult('The "foobar" event'),
            $listener(new ResolvedInsertTag('event_title', new ResolvedParameters(['2']), [])),
        );

        $this->assertSame(
            '<p>The annual foobar event.</p>',
            $listener(new ResolvedInsertTag('event_teaser', new ResolvedParameters(['2']), []))->getValue(),
        );
    }

    public function testReturnsAnEmptyStringIfThereIsNoModel(): void
    {
        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByIdOrAlias' => null]),
            CalendarFeedModel::class => $this->mockConfiguredAdapter(['findById' => null]),
        ];

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $listener = new EventInsertTag($this->mockContaoFramework($adapters), $urlGenerator);

        $this->assertSame('', $listener(new ResolvedInsertTag('calendar_feed', new ResolvedParameters(['3']), []))->getValue());
        $this->assertSame('', $listener(new ResolvedInsertTag('event_url', new ResolvedParameters(['3']), []))->getValue());
    }
}
