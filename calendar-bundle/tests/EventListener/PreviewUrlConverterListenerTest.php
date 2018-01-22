<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CalendarBundle\EventListener\PreviewUrlConvertListener;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the PreviewUrlConverterListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlConverterListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\PreviewUrlConvertListener', $listener);
    }

    /**
     * Tests the onPreviewUrlConvert() method.
     */
    public function testConvertsThePreviewUrl()
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

        $this->assertSame('http://localhost/events/winter-holidays.html', $event->getUrl());
    }

    /**
     * Tests that the listener is bypassed if the framework is not initialized.
     */
    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized()
    {
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    /**
     * Tests that the listener is bypassed if there is no "calendar" parameter.
     */
    public function testDoesNotConvertThePreviewUrlIfTheCalendarParameterIsNotSet()
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
    public function testDoesNotConvertThePreviewUrlIfThereIsNoEvent()
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
     * @param bool $isInitialized
     *
     * @return ContaoFrameworkInterface
     */
    private function mockContaoFramework($isInitialized = true)
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn($isInitialized)
        ;

        $eventsAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['generateEventUrl'])
            ->getMock()
        ;

        $eventsAdapter
            ->method('generateEventUrl')
            ->willReturn('events/winter-holidays.html')
        ;

        $eventsModelAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['findByPk'])
            ->getMock()
        ;

        $eventsModelAdapter
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
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($eventsAdapter, $eventsModelAdapter) {
                switch ($key) {
                    case Events::class:
                        return $eventsAdapter;

                    case CalendarEventsModel::class:
                        return $eventsModelAdapter;

                    default:
                        return null;
                }
            })
        ;

        return $framework;
    }
}
