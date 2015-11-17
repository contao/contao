<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Test\EventListener;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Events;
use Contao\CalendarBundle\EventListener\PreviewUrlConvertListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the PreviewUrlConverterListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlConverterListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\PreviewUrlConvertListener', $listener);
    }

    /**
     * Tests the onPreviewUrlConvert() method.
     */
    public function testOnPreviewUrlConvert()
    {
        $request = Request::createFromGlobals();
        $request->query->set('calendar', 1);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlConvert($event);

        $this->assertEquals('http://localhost/events/winter-holidays.html', $event->getUrl());
    }

    /**
     * Tests that the listener is bypassed if the framework is not initialized.
     */
    public function testBypassIfFrameworkNotInitialized()
    {
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    /**
     * Tests that the listener is bypassed if there is no "calendar" parameter.
     */
    public function testBypassIfNoCalendarParameter()
    {
        $request = Request::createFromGlobals();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    /**
     * Tests that the listener is bypassed if there is no event.
     */
    public function testBypassIfNoEvent()
    {
        $request = Request::createFromGlobals();
        $request->query->set('calendar', null);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @param bool $isInitialized The initialization status
     *
     * @return ContaoFrameworkInterface The object instance
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

        /** @var Events|\PHPUnit_Framework_MockObject_MockObject $framework */
        $events = $this->getMock('Contao\Events', ['generateEventUrl']);

        $events
            ->expects($this->any())
            ->method('generateEventUrl')
            ->willReturn('events/winter-holidays.html')
        ;

        /** @var CalendarEventsModel|\PHPUnit_Framework_MockObject_MockObject $framework */
        $eventsModel = $this->getMock('Contao\CalendarEventsModel', ['findByPk']);

        $eventsModel
            ->expects($this->any())
            ->method('findByPk')
            ->willReturnCallback(function ($id) {
                switch ($id) {
                    case null:
                        return null;

                    default:
                        return [];
                }
            })
        ;

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($events, $eventsModel) {
                switch ($key) {
                    case 'Contao\Events':
                        return $events;

                    case 'Contao\CalendarEventsModel':
                        return $eventsModel;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
