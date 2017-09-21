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
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Events;
use PHPUnit\Framework\TestCase;

class InsertTagsListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\InsertTagsListener', $listener);
    }

    public function testReplacesTheCalendarFeedTag(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            'http://localhost/share/events.xml',
            $listener->onReplaceInsertTags('calendar_feed::2')
        );
    }

    public function testReplacesTheEventTags(): void
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

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
        $listener = new InsertTagsListener($this->mockContaoFramework('source', true));

        $this->assertSame('', $listener->onReplaceInsertTags('calendar_feed::3'));
        $this->assertSame('', $listener->onReplaceInsertTags('event_url::3'));
    }

    /**
     * Mocks the Contao framework.
     *
     * @param string $source
     * @param bool   $noModels
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockContaoFramework(string $source = 'default', bool $noModels = false): ContaoFrameworkInterface
    {
        $feedModel = $this->createMock(CalendarFeedModel::class);

        $feedModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key): ?string {
                    switch ($key) {
                        case 'feedBase':
                            return 'http://localhost/';

                        case 'alias':
                            return 'events';
                    }

                    return null;
                }
            )
        ;

        $calendarFeedModelAdapter = $this->createMock(Adapter::class);

        $calendarFeedModelAdapter
            ->method('__call')
            ->willReturn($noModels ? null : $feedModel)
        ;

        $eventModel = $this->createMock(CalendarEventsModel::class);

        $eventModel
            ->method('__get')
            ->willReturnCallback(
                function (string $key) use ($source): ?string {
                    switch ($key) {
                        case 'title':
                            return 'The "foobar" event';

                        case 'teaser':
                            return '<p>The annual foobar event.</p>';
                    }

                    return null;
                }
            )
        ;

        $eventsModelAdapter = $this->createMock(Adapter::class);

        $eventsModelAdapter
            ->method('__call')
            ->willReturn($noModels ? null : $eventModel)
        ;

        $eventsAdapter = $this->createMock(Adapter::class);

        $eventsAdapter
            ->method('__call')
            ->willReturn('events/the-foobar-event.html')
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($calendarFeedModelAdapter, $eventsModelAdapter, $eventsAdapter): ?Adapter {
                    switch ($key) {
                        case CalendarFeedModel::class:
                            return $calendarFeedModelAdapter;

                        case CalendarEventsModel::class:
                            return $eventsModelAdapter;

                        case Events::class:
                            return $eventsAdapter;
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }
}
