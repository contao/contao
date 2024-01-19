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

use Contao\CalendarBundle\EventListener\PreviewUrlConvertListener;
use Contao\CalendarEventsModel;
use Contao\CoreBundle\Event\PreviewUrlConvertEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PreviewUrlConverterListenerTest extends ContaoTestCase
{
    public function testConvertsThePreviewUrl(): void
    {
        $request = new Request();
        $request->query->set('calendar', 1);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $eventModel = $this->createMock(CalendarEventsModel::class);

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByPk' => $eventModel]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($eventModel, [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://localhost/events/winter-holidays.html')
        ;

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertSame('http://localhost/events/winter-holidays.html', $event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new PreviewUrlConvertEvent(new Request());

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfTheCalendarParameterIsNotSet(): void
    {
        $request = new Request();
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $framework = $this->mockContaoFramework();

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertNull($event->getUrl());
    }

    public function testDoesNotConvertThePreviewUrlIfThereIsNoEvent(): void
    {
        $request = new Request();
        $request->query->set('calendar', null);
        $request->server->set('SERVER_NAME', 'localhost');
        $request->server->set('SERVER_PORT', 80);

        $adapters = [
            CalendarEventsModel::class => $this->mockConfiguredAdapter(['findByPk' => null]),
        ];

        $framework = $this->mockContaoFramework($adapters);

        $urlGenerator = $this->createMock(ContentUrlGenerator::class);
        $urlGenerator
            ->expects($this->never())
            ->method($this->anything())
        ;

        $event = new PreviewUrlConvertEvent($request);

        $listener = new PreviewUrlConvertListener($framework, $urlGenerator);
        $listener($event);

        $this->assertNull($event->getUrl());
    }
}
