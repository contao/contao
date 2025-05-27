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
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;

class PreviewUrlCreateListenerTest extends ContaoTestCase
{
    public function testCreatesThePreviewUrl(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_calendar_events', 1])
        ;

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $this->createMock(Connection::class));
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

        $listener = new PreviewUrlCreateListener($framework, $this->createMock(DcaUrlAnalyzer::class), $this->createMock(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheCalendarParameterIsNotSet(): void
    {
        $framework = $this->mockContaoFramework();
        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $this->createMock(DcaUrlAnalyzer::class), $this->createMock(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheCalendarListPage(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_calendar', null])
        ;

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $this->createMock(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheCalendarEditPage(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_calendar', 1])
        ;

        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $this->createMock(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testCreatesThePreviewUrlForEvents(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_calendar_events', 2])
        ;

        $event = new PreviewUrlCreateEvent('calendar', 2);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $this->createMock(Connection::class));
        $listener($event);

        $this->assertSame('calendar=2', $event->getQuery());
    }

    public function testCreatesThePreviewUrlForContentElements(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_content', 18])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([['pid' => 2, 'ptable' => 'tl_calendar_events']])
        ;

        $event = new PreviewUrlCreateEvent('calendar', 18);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $connection);
        $listener($event);

        $this->assertSame('calendar=2', $event->getQuery());
    }
}
