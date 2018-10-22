<?php

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
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the PreviewUrlCreateListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class PreviewUrlCreateListenerTest extends TestCase
{
    /**
     * Tests the onPreviewUrlCreate() method.
     */
    public function testCreatesThePreviewUrl()
    {
        $request = Request::createFromGlobals();

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($requestStack, $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertSame('calendar=1', $event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if the framework is not initialized.
     */
    public function testDoesNotCreateThePreviewUrlIfTheFrameworkIsNotInitialized()
    {
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework(false));
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the listener is bypassed if the key is not "calendar".
     */
    public function testDoesNotCreateThePreviewUrlIfTheCalendarParameterIsNotSet()
    {
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener(new RequestStack(), $this->mockContaoFramework());
        $listener->onPreviewUrlCreate($event);

        $this->assertNull($event->getQuery());
    }

    /**
     * Tests that the listener is bypassed on the calendar list page.
     */
    public function testDoesNotCreateThePreviewUrlOnTheCalendarListPage()
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
    public function testOverwritesTheIdIfTheEventSettingsAreEdited()
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

    /**
     * Tests that the listener is bypassed if there is no event.
     */
    public function testDoesNotCreateThePreviewUrlIfThereIsNoEvent()
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
        $framework = $this->createMock(ContaoFrameworkInterface::class);

        $framework
            ->method('isInitialized')
            ->willReturn($isInitialized)
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
                if (null === $id) {
                    return null;
                }

                return (object)['id' => $id];
            })
        ;

        $framework
            ->method('getAdapter')
            ->willReturnCallback(function ($key) use ($eventsModelAdapter) {
                if (CalendarEventsModel::class === $key) {
                    return $eventsModelAdapter;
                }

                return null;
            })
        ;

        return $framework;
    }
}
