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

use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Doctrine\DBAL\ParentQuery;
use Contao\CoreBundle\Event\PreviewUrlCreateEvent;
use Contao\CoreBundle\EventListener\PreviewUrlCreateListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Constraint\Callback;

class PreviewUrlCreateListenerTest extends TestCase
{
    public function testCreatesThePreviewUrlForPages(): void
    {
        $event = new PreviewUrlCreateEvent('page', 42);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $this->createStub(DcaUrlAnalyzer::class), $this->createStub(DcaHierarchy::class));
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

        $dcaHierarchy = $this->createMock(DcaHierarchy::class);
        $dcaHierarchy
            ->expects($this->once())
            ->method('getParentRows')
            ->with(
                3,
                'tl_article',
                $this->isSingleParentRowQuery(),
            )
            ->willReturn([['id' => 3, 'pid' => 42]])
        ;

        $event = new PreviewUrlCreateEvent('article', 3);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $dcaHierarchy);
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

        $dcaHierarchy = $this->createMock(DcaHierarchy::class);
        $dcaHierarchy
            ->expects($this->once())
            ->method('getParentTableAndId')
            ->with(18, 'tl_content')
            ->willReturn(['tl_article', 3])
        ;

        $dcaHierarchy
            ->expects($this->once())
            ->method('getParentRows')
            ->with(
                3,
                'tl_article',
                $this->isSingleParentRowQuery(),
            )
            ->willReturn([['id' => 3, 'pid' => 42]])
        ;

        $event = new PreviewUrlCreateEvent('article', 3);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $dcaHierarchy);
        $listener($event);

        $this->assertSame('page=42', $event->getQuery());
    }

    #[DataProvider('getValidDoParameters')]
    public function testDoesNotCreateAnyPreviewUrlIfTheFrameworkIsNotInitialized(string $do): void
    {
        $framework = $this->createStub(ContaoFramework::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $event = new PreviewUrlCreateEvent($do, 42);

        $listener = new PreviewUrlCreateListener($framework, $this->createStub(DcaUrlAnalyzer::class), $this->createStub(DcaHierarchy::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    #[DataProvider('getInvalidDoParameters')]
    public function testDoesNotCreateThePreviewUrlIfNeitherPageNorArticleParameterIsSet(string $do): void
    {
        $event = new PreviewUrlCreateEvent($do, 1);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $this->createStub(DcaUrlAnalyzer::class), $this->createStub(DcaHierarchy::class));
        $listener($event);

        $this->assertNull($event->getQuery());
    }

    #[DataProvider('getValidDoParameters')]
    public function testDoesNotCreateThePreviewUrlIfThereIsNoId(string $do): void
    {
        $dcaUrlAnalyzer = $this->createStub(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->method('getCurrentTableId')
            ->willReturn(['tl_article', null])
        ;

        $event = new PreviewUrlCreateEvent($do, 0);

        $listener = new PreviewUrlCreateListener($this->createContaoFrameworkStub(), $dcaUrlAnalyzer, $this->createStub(DcaHierarchy::class));
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

    /**
     * @return Callback<ParentQuery>
     */
    private function isSingleParentRowQuery(): Callback
    {
        return $this->callback(static fn (ParentQuery $query): bool => $query->includesBoundaryRow() && 1 === $query->maxDepth());
    }
}
