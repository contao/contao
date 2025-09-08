<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\EventListener\PreviewUrlCreateListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;

class PreviewUrlCreateListenerTest extends TestCase
{
    public function testCreatesThePreviewUrlForPages(): void
    {
        $event = new PreviewUrlCreateEvent('page', 42);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $this->createMock(DcaUrlAnalyzer::class), $this->createMock(Connection::class));
        $listener($event);

        $this->assertSame('page=42', $event->getQuery());
    }

    public function testCreatesThePreviewUrlForArticles(): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getCurrentTableId')
            ->willReturn(['tl_article', 3])
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT pid FROM tl_article WHERE id=?', [3])
            ->willReturn(42)
        ;

        $event = new PreviewUrlCreateEvent('article', 3);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $connection);
        $listener($event);

        $this->assertSame('page=42', $event->getQuery());
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
            ->willReturn([['pid' => 3, 'ptable' => 'tl_article']])
        ;

        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT pid FROM tl_article WHERE id=?', [3])
            ->willReturn(42)
        ;

        $event = new PreviewUrlCreateEvent('article', 3);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $connection);
        $listener($event);

        $this->assertSame('page=42', $event->getQuery());
    }

    #[DataProvider('getValidDoParameters')]
    public function testDoesNotCreateAnyPreviewUrlIfTheFrameworkIsNotInitialized(string $do): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlCreateEvent($do, 42);

        $listener = new PreviewUrlCreateListener($framework, $this->createMock(DcaUrlAnalyzer::class), $this->createMock(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    #[DataProvider('getInvalidDoParameters')]
    public function testDoesNotCreateThePreviewUrlIfNeitherPageNorArticleParameterIsSet(string $do): void
    {
        $event = new PreviewUrlCreateEvent($do, 1);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $this->createMock(DcaUrlAnalyzer::class), $this->createMock(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    #[DataProvider('getValidDoParameters')]
    public function testDoesNotCreateThePreviewUrlIfThereIsNoId(string $do): void
    {
        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->method('getCurrentTableId')
            ->willReturn(['tl_article', null])
        ;

        $event = new PreviewUrlCreateEvent($do, 0);

        $listener = new PreviewUrlCreateListener($this->mockContaoFramework(), $dcaUrlAnalyzer, $this->createMock(Connection::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    public static function getValidDoParameters(): iterable
    {
        yield ['page'];
        yield ['article'];
    }

    public static function getInvalidDoParameters(): iterable
    {
        yield [''];
        yield ['news'];
        yield ['calendar'];
    }
}
