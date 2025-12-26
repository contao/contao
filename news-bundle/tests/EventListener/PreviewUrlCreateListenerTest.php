<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsBundle\EventListener\PreviewUrlCreateListener;
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
            ->willReturn(['tl_news', 1])
        ;

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $this->createStub(Connection::class));
        $listener($event);

        $this->assertSame('news=1', $event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createStub(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($framework, $this->createStub(DcaUrlAnalyzer::class), $this->createStub(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlIfTheNewsParameterIsNotSet(): void
    {
        $framework = $this->createContaoFrameworkStub();
        $event = new PreviewUrlCreateEvent('calendar', 1);

        $listener = new PreviewUrlCreateListener($framework, $this->createStub(DcaUrlAnalyzer::class), $this->createStub(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheArchiveListPage(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_news_archive', null])
        ;

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $this->createStub(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testDoesNotCreateThePreviewUrlOnTheArchiveEditPage(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_news_archive', 1])
        ;

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $this->createStub(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public function testCreatesThePreviewUrlForNews(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_news', 2])
        ;

        $event = new PreviewUrlCreateEvent('news', 1);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $this->createStub(Connection::class));
        $listener($event);

        $this->assertSame('news=2', $event->getQuery());
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
            ->willReturn([['pid' => 2, 'ptable' => 'tl_news']])
        ;

        $event = new PreviewUrlCreateEvent('news', 18);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $connection);
        $listener($event);

        $this->assertSame('news=2', $event->getQuery());
    }
}
