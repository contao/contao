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

use Contao\CalendarBundle\EventListener\PreviewUrlCreateListener;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlCreateListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework());

        $this->assertInstanceOf('Contao\CalendarBundle\EventListener\PreviewUrlCreateListener', $listener);
    }

    public function testCreatesThePreviewUrl(): void
    {
        $request = Request::createFromGlobals();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertSame('calendar=1', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheCalendarParameterIsNotSet(): void
    {
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheCalendarListPage(): void
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

    public function testOverwritesTheIdIfTheEventSettingsAreEdited(): void
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

        $this->assertSame('calendar=2', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfThereIsNoEvent(): void
    {
        $request = Request::createFromGlobals();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('calendar', 0);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
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

        $eventsModelAdapter = $this->createMock(Adapter::class);

        $eventsModelAdapter
            ->method('__call')
            ->willReturnCallback(
                function (string $method, array $params): ?CalendarEventsModel {
                    $this->assertInternalType('string', $method);

                    if (!empty($params[0])) {
                        $adapter = $this->createMock(CalendarEventsModel::class);

                        $adapter
                            ->method('__get')
                            ->willReturn($params[0])
                        ;

                        return $adapter;
                    }

                    return null;
                }
            )
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(
                function (string $key) use ($eventsModelAdapter): ?Adapter {
                    if (CalendarEventsModel::class === $key) {
                        return $eventsModelAdapter;
                    }

                    return null;
                }
            )
        ;

        return $framework;
    }
}
