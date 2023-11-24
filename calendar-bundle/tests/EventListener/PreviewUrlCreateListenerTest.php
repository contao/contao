<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\CalendarBundle\EventListener\PreviewUrlCreateListener;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class PreviewUrlCreateListenerTest extends ContaoTestCase
{
    public function testCreatesThePreviewUrl(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class);
        $eventModel->id = 1;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByPk' => $eventModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertSame('calendar=1', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheCalendarParameterIsNotSet(): void
    {
        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheCalendarListPage(): void
    {
        $request = new Request();
        $request->query->set('table', 'tl_calendar_events');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testOverwritesTheIdIfTheEventSettingsAreEdited(): void
    {
        $request = new Request();
        $request->query->set('act', 'edit');
        $request->query->set('table', 'tl_calendar_events');
        $request->query->set('id', 2);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $eventModel = $this->mockClassWithProperties(CalendarEventsModel::class);
        $eventModel->id = 2;

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByPk' => $eventModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlCreateEvent('calendar', 2);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertSame('calendar=2', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfThereIsNoEvent(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);
        $event = new PreviewUrlCreateEvent('calendar', 0);

        $listener = new PreviewUrlCreateListener($requestStack, $framework);
        $listener($event);

        $this->assertNull($event->getQuery());
    }
}
