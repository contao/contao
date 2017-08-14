<?php

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

/**
 * Tests the InsertTagsListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsertTagsListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\InsertTagsListener', $listener);
    }

    /**
     * Tests that the listener returns a replacement string for a calendar feed.
     */
    public function testReturnFeedReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertSame(
            'http://localhost/share/events.xml',
            $listener->onReplaceInsertTags('calendar_feed::2')
        );
    }

    /**
     * Tests that the listener returns a replacement string for an event.
     */
    public function testReturnEventReplacementString()
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

    /**
     * Tests that the listener returns false if the tag is unknown.
     */
    public function testReturnFalseIfTagUnknown()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework());

        $this->assertFalse($listener->onReplaceInsertTags('link_url::2'));
    }

    /**
     * Tests that the listener returns an empty string if there is no model.
     */
    public function testReturnEmptyStringIfNoModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', true));

        $this->assertSame('', $listener->onReplaceInsertTags('calendar_feed::3'));
        $this->assertSame('', $listener->onReplaceInsertTags('event_url::3'));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param string $source
     * @param bool   $noModels
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($source = 'default', $noModels = false)
    {
        $feedModel = $this->createMock(CalendarFeedModel::class);

        $feedModel
            ->method('__get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'feedBase':
                        return 'http://localhost/';

                    case 'alias':
                        return 'events';

                    default:
                        return null;
                }
            })
        ;

        $calendarFeedModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByPk'])
            ->getMock()
        ;

        $calendarFeedModelAdapter
            ->method('findByPk')
            ->willReturn($noModels ? null : $feedModel)
        ;

        $eventModel = $this->createMock(CalendarEventsModel::class);

        $eventModel
            ->method('__get')
            ->willReturnCallback(function ($key) use ($source) {
                switch ($key) {
                    case 'title':
                        return 'The "foobar" event';

                    case 'teaser':
                        return '<p>The annual foobar event.</p>';

                    default:
                        return null;
                }
            })
        ;

        $eventsModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByIdOrAlias'])
            ->getMock()
        ;

        $eventsModelAdapter
            ->method('findByIdOrAlias')
            ->willReturn($noModels ? null : $eventModel)
        ;

        $eventsAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateEventUrl'])
            ->getMock()
        ;

        $eventsAdapter
            ->method('generateEventUrl')
            ->willReturn('events/the-foobar-event.html')
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($calendarFeedModelAdapter, $eventsModelAdapter, $eventsAdapter) {
                switch ($key) {
                    case CalendarFeedModel::class:
                        return $calendarFeedModelAdapter;

                    case CalendarEventsModel::class:
                        return $eventsModelAdapter;

                    case Events::class:
                        return $eventsAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
