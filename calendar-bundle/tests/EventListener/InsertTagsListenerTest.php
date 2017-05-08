<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\ArticleModel;
use Contao\CalendarBundle\EventListener\InsertTagsListener;
use Contao\CalendarEventsModel;
use Contao\CalendarFeedModel;
use Contao\CalendarModel;
use Contao\Config;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\PageModel;
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
     * Tests that the listener returns a replacement string for an event with an exernal target.
     */
    public function testReturnEventWithExternalTargetReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('external'));

        $this->assertSame(
            '<a href="https://contao.org" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertSame(
            '<a href="https://contao.org" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertSame(
            'https://contao.org',
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
     * Tests that the listener returns a replacement string for an event with an internal target.
     */
    public function testReturnEventWithInteralTargetReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('internal'));

        $this->assertSame(
            '<a href="internal-target.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertSame(
            '<a href="internal-target.html" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertSame(
            'internal-target.html',
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
     * Tests that the listener returns a replacement string for an event with an article target.
     */
    public function testReturnEventWithArticleTargetReplacementString()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('article'));

        $this->assertSame(
            '<a href="portfolio/articles/foobar.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertSame(
            '<a href="portfolio/articles/foobar.html" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertSame(
            'portfolio/articles/foobar.html',
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
     * Tests that the listener returns an empty string if there is no calendar model.
     */
    public function testReturnEmptyStringIfNoCalendarModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', false, true));

        $this->assertSame('', $listener->onReplaceInsertTags('event_url::3'));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param string $source
     * @param bool   $noModels
     * @param bool   $noCalendar
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($source = 'default', $noModels = false, $noCalendar = false)
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

        $page = $this->createMock(PageModel::class);

        $page
            ->method('getFrontendUrl')
            ->willReturn('events/the-foobar-event.html')
        ;

        $calendarModel = $this->createMock(CalendarModel::class);

        $calendarModel
            ->method('getRelated')
            ->willReturn($page)
        ;

        $jumpTo = $this->createMock(PageModel::class);

        $jumpTo
            ->method('getFrontendUrl')
            ->willReturn('internal-target.html')
        ;

        $pid = $this->createMock(PageModel::class);

        $pid
            ->method('getFrontendUrl')
            ->willReturn('portfolio/articles/foobar.html')
        ;

        $articleModel = $this->createMock(ArticleModel::class);

        $articleModel
            ->method('getRelated')
            ->willReturn($pid)
        ;

        $eventModel = $this->createMock(CalendarEventsModel::class);

        $eventModel
            ->method('getRelated')
            ->willReturnCallback(function ($key) use ($jumpTo, $articleModel, $calendarModel, $noCalendar) {
                switch ($key) {
                    case 'jumpTo':
                        return $jumpTo;

                    case 'articleId':
                        return $articleModel;

                    case 'pid':
                        return $noCalendar ? null : $calendarModel;

                    default:
                        return null;
                }
            })
        ;

        $eventModel
            ->method('__get')
            ->willReturnCallback(function ($key) use ($source) {
                switch ($key) {
                    case 'source':
                        return $source;

                    case 'id':
                        return 2;

                    case 'alias':
                        return 'the-foobar-event';

                    case 'title':
                        return 'The "foobar" event';

                    case 'teaser':
                        return '<p>The annual foobar event.</p>';

                    case 'url':
                        return 'https://contao.org';

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

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($calendarFeedModelAdapter, $eventsModelAdapter) {
                switch ($key) {
                    case CalendarFeedModel::class:
                        return $calendarFeedModelAdapter;

                    case CalendarEventsModel::class:
                        return $eventsModelAdapter;

                    case Config::class:
                        return $this->mockConfigAdapter();

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }

    /**
     * Mocks a config adapter.
     *
     * @return Adapter|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockConfigAdapter()
    {
        $configAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->getMock()
        ;

        $configAdapter
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->method('get')
            ->willReturnCallback(function ($key) {
                switch ($key) {
                    case 'characterSet':
                        return 'UTF-8';

                    case 'useAutoItem':
                        return true;

                    default:
                        return null;
                }
            })
        ;

        return $configAdapter;
    }
}
