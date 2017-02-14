<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Test\EventListener;

use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CalendarBundle\EventListener\PreviewUrlCreateListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the PreviewUrlCreateListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlCreateListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\PreviewUrlCreateListener', $listener);
    }

    /**
     * Tests the onPreviewUrlCreate() method.
     */
    public function testOnPreviewUrlCreate()
    {
        $request = Request::createFromGlobals();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertEquals('calendar=1', $event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if the framework is not initialized.
     */
    public function testBypassIfFrameworkNotInitialized()
    {
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if the key is not "calendar".
     */
    public function testBypassUponForeignKey()
    {
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the listener is bypassed on the calendar list page.
     */
    public function testBypassOnCalendarListPage()
    {
        $request = Request::createFromGlobals();
        $request->query->set('table', 'tl_calendar_events');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the ID is overwritten if the event settings are edited.
     */
    public function testIdOverwrittenInEventSettings()
    {
        $request = Request::createFromGlobals();
        $request->query->set('act', 'edit');
        $request->query->set('table', 'tl_calendar_events');
        $request->query->set('id', 2);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertEquals('calendar=2', $event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if there is no event.
     */
    public function testBypassIfNoEvent()
    {
        $request = Request::createFromGlobals();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('calendar', null);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param bool $isInitialized
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($isInitialized = true)
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
            ->willReturn($isInitialized)
        ;

        $eventsModelAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->disableOriginalConstructor()
            ->setMethods(['findByPk'])
            ->getMock()
        ;

        $eventsModelAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturnCallback(function ($id) {
                switch ($id) {
                    case null:
                        return null;

                    default:
                        return (object) ['id' => $id];
                }
            })
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($eventsModelAdapter) {
                switch ($key) {
                    case 'Contao\CalendarEventsModel':
                        return $eventsModelAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
