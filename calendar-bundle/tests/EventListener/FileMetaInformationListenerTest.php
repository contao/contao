<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Test\EventListener;

use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CalendarBundle\EventListener\FileMetaInformationListener;
use Contao\PageModel;

/**
 * Tests the FileMetaInformationListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FileMetaInformationListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\FileMetaInformationListener', $listener);
    }

    /**
     * Tests that the listener returns a page model.
     */
    public function testReturnPageModel()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertInstanceOf(
            'Contao\PageModel',
            $listener->onAddFileMetaInformationToRequest('tl_calendar', 2)
        );

        $this->assertInstanceOf(
            'Contao\PageModel',
            $listener->onAddFileMetaInformationToRequest('tl_calendar_events', 2)
        );
    }

    /**
     * Tests that the listener returns false if the table is unknown.
     */
    public function testReturnFalseIfTableUnknown()
    {
        $listener = new FileMetaInformationListener($this->mockContaoFramework());

        $this->assertFalse($listener->onAddFileMetaInformationToRequest('invalid', 2));
    }

    /**
     * Returns a ContaoFramework instance.
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework()
    {
        /** @var PageModel|\PHPUnit_Framework_MockObject_MockObject $pageModel */
        $pageModel = $this
            ->getMockBuilder('Contao\PageModel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /** @var CalendarModel|\PHPUnit_Framework_MockObject_MockObject $pageModel */
        $calendarModel = $this
            ->getMockBuilder('Contao\CalendarModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $calendarModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($pageModel)
        ;

        $calendarAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByPk'])
            ->setConstructorArgs(['Contao\CalendarModel'])
            ->getMock()
        ;

        $calendarAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturn($calendarModel)
        ;

        /** @var CalendarEventsModel|\PHPUnit_Framework_MockObject_MockObject $pageModel */
        $eventsModel = $this
            ->getMockBuilder('Contao\CalendarEventsModel')
            ->setMethods(['getRelated'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $eventsModel
            ->expects($this->any())
            ->method('getRelated')
            ->willReturn($calendarModel)
        ;

        $eventsAdapter = $this
            ->getMockBuilder('Contao\CoreBundle\Framework\Adapter')
            ->setMethods(['findByPk'])
            ->setConstructorArgs(['Contao\CalendarEventsModel'])
            ->getMock()
        ;

        $eventsAdapter
            ->expects($this->any())
            ->method('findByPk')
            ->willReturn($eventsModel)
        ;

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

        $framework
            ->expects($this->any())
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($calendarAdapter, $eventsAdapter) {
                switch ($key) {
                    case 'Contao\CalendarModel':
                        return $calendarAdapter;

                    case 'Contao\CalendarEventsModel':
                        return $eventsAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
