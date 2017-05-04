<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CalendarBundle\EventListener\InsertTagsListener;

/**
 * Tests the InsertTagsListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InsertTagsListenerTest extends \PHPUnit_Framework_TestCase
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

        $this->assertEquals(
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

        $this->assertEquals(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertEquals(
            '<a href="events/the-foobar-event.html" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertEquals(
            'events/the-foobar-event.html',
            $listener->onReplaceInsertTags('event_url::2')
        );

        $this->assertEquals(
            'The &quot;foobar&quot; event',
            $listener->onReplaceInsertTags('event_title::2')
        );

        $this->assertEquals(
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

        $this->assertEquals(
            '<a href="https://contao.org" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertEquals(
            '<a href="https://contao.org" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertEquals(
            'https://contao.org',
            $listener->onReplaceInsertTags('event_url::2')
        );

        $this->assertEquals(
            'The &quot;foobar&quot; event',
            $listener->onReplaceInsertTags('event_title::2')
        );

        $this->assertEquals(
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

        $this->assertEquals(
            '<a href="internal-target.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertEquals(
            '<a href="internal-target.html" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertEquals(
            'internal-target.html',
            $listener->onReplaceInsertTags('event_url::2')
        );

        $this->assertEquals(
            'The &quot;foobar&quot; event',
            $listener->onReplaceInsertTags('event_title::2')
        );

        $this->assertEquals(
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

        $this->assertEquals(
            '<a href="portfolio/articles/foobar.html" title="The &quot;foobar&quot; event">The "foobar" event</a>',
            $listener->onReplaceInsertTags('event::2')
        );

        $this->assertEquals(
            '<a href="portfolio/articles/foobar.html" title="The &quot;foobar&quot; event">',
            $listener->onReplaceInsertTags('event_open::2')
        );

        $this->assertEquals(
            'portfolio/articles/foobar.html',
            $listener->onReplaceInsertTags('event_url::2')
        );

        $this->assertEquals(
            'The &quot;foobar&quot; event',
            $listener->onReplaceInsertTags('event_title::2')
        );

        $this->assertEquals(
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

        $this->assertEquals('', $listener->onReplaceInsertTags('calendar_feed::3'));
        $this->assertEquals('', $listener->onReplaceInsertTags('event_url::3'));
    }

    /**
     * Tests that the listener returns an empty string if there is no calendar model.
     */
    public function testReturnEmptyStringIfNoCalendarModel()
    {
        $listener = new InsertTagsListener($this->mockContaoFramework('source', false, true));

        $this->assertEquals('', $listener->onReplaceInsertTags('event_url::3'));
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
        /** @var ContaoFramework|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\ContaoFramework')
            ->disableOriginalConstructor()
            ->setMethods(['isInitialized', 'getAdapter'])
            ->getMock()
        ;

        $framework
            ->expects($this->any())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $feedModel = $this
            ->getMockBuilder('Contao\CalendarFeedModel')
            ->setMethods(['__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $feedModel
            ->expects($this->any())
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
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByPk'])
            ->setConstructorArgs(['Contao\CalendarFeedModel'])
            ->getMock()
        ;

        $calendarFeedModelAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturn($noModels ? null : $feedModel)
        ;

        $page = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getFrontendUrl'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $page
            ->expects($this->any())
            ->method('getFrontendUrl')
            ->willReturn('events/the-foobar-event.html')
        ;

        $calendarModel = $this
            ->getMockBuilder('Contao\CalendarModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $calendarModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($page)
        ;

        $jumpTo = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getFrontendUrl'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $jumpTo
            ->expects($this->any())
            ->method('getFrontendUrl')
            ->willReturn('internal-target.html')
        ;

        $pid = $this
            ->getMockBuilder('Contao\PageModel')
            ->setMethods(['getFrontendUrl'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $pid
            ->expects($this->any())
            ->method('getFrontendUrl')
            ->willReturn('portfolio/articles/foobar.html')
        ;

        $articleModel = $this
            ->getMockBuilder('Contao\ArticleModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $articleModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($pid)
        ;

        $eventModel = $this
            ->getMockBuilder('Contao\CalendarEventsModel')
            ->setMethods(['getRelated', '__get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $eventModel
            ->expects($this->any())
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
            ->expects($this->any())
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
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByIdOrAlias'])
            ->setConstructorArgs(['Contao\CalendarEventsModel'])
            ->getMock()
        ;

        $eventsModelAdapter
            ->expects($this->any())
            ->method('findByIdOrAlias')
            ->willReturn($noModels ? null : $eventModel)
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($calendarFeedModelAdapter, $eventsModelAdapter) {
                switch ($key) {
                    case 'Contao\CalendarFeedModel':
                        return $calendarFeedModelAdapter;

                    case 'Contao\CalendarEventsModel':
                        return $eventsModelAdapter;

                    case 'Contao\Config':
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
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['isComplete', 'preload', 'getInstance', 'get'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $configAdapter
            ->expects($this->any())
            ->method('isComplete')
            ->willReturn(true)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('preload')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
            ->method('getInstance')
            ->willReturn(null)
        ;

        $configAdapter
            ->expects($this->any())
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
