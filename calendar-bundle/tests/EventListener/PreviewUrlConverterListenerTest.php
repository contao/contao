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

use Contao\CalendarBundle\EventListener\PreviewUrlConvertListener;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlConverterListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\PreviewUrlConvertListener', $listener);
    }

    public function testConvertsThePreviewUrl(): void
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

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $event = new PreviewUrlConvertEvent();

        $listener = new PreviewUrlConvertListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlConvert($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheCalendarParameterIsNotSet(): void
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

    public function testDoesNotConvertThePreviewUrlIfThereIsNoEvent(): void
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
     * Mocks the Contao framework.
     *
     * @param bool $isInitialized
     *
     * @return ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockContaoFramework(bool $isInitialized = true): ContaoFrameworkInterface
    {
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn($isInitialized)
        ;

        $eventsAdapter = $this->createMock(Adapter::class);

        $eventsAdapter
            ->method('__call')
            ->willReturn('events/winter-holidays.html')
        ;

        $eventsModelAdapter = $this->createMock(Adapter::class);

        $eventsModelAdapter
            ->method('__call')
            ->willReturnCallback(
                function (string $method, array $params): ?CalendarEventsModel {
                    $this->assertInternalType('string', $method);

                    if (!empty($params[0])) {
                        return $this->createMock(CalendarEventsModel::class);
                    }

                    return null;
                }
            )
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($eventsAdapter, $eventsModelAdapter): ?Adapter {
                    switch ($key) {
                        case Events::class:
                            return $eventsAdapter;

                        case CalendarEventsModel::class:
                            return $eventsModelAdapter;
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }
}
