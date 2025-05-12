<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Indexer;

use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\DelegatingIndexer;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use PHPUnit\Framework\TestCase;

class DelegatingIndexerTest extends TestCase
{
    public function testDelegatesTheMethodCalls(): void
    {
        $indexer1 = $this->createIndexer();
        $indexer2 = $this->createIndexer();

        $delegating = new DelegatingIndexer();
        $delegating->addIndexer($indexer1);
        $delegating->addIndexer($indexer2);
        $delegating->index($this->createMock(Document::class));
        $delegating->delete($this->createMock(Document::class));
        $delegating->clear();
    }

    public function testDelegatesAndCollectsIndexerExceptions(): void
    {
        $indexer1 = $this->createIndexer(IndexerException::createAsWarning('Warning 1'));
        $indexer2 = $this->createIndexer(new IndexerException('Failure 2'));
        $indexer3 = $this->createIndexer();

        $delegating = new DelegatingIndexer();
        $delegating->addIndexer($indexer1);
        $delegating->addIndexer($indexer2);
        $delegating->addIndexer($indexer3);

        try {
            $delegating->index($this->createMock(Document::class));
        } catch (IndexerException $exception) {
            $this->assertSame('Warning 1 | Failure 2', $exception->getMessage());
            $this->assertFalse($exception->isOnlyWarning());
        }

        try {
            $delegating->delete($this->createMock(Document::class));
        } catch (IndexerException $exception) {
            $this->assertSame('Warning 1 | Failure 2', $exception->getMessage());
            $this->assertFalse($exception->isOnlyWarning());
        }

        try {
            $delegating->clear();
        } catch (IndexerException $exception) {
            $this->assertSame('Warning 1 | Failure 2', $exception->getMessage());
            $this->assertFalse($exception->isOnlyWarning());
        }
    }

    public function testPrioritizesGeneralExceptionsOverIndexerExceptions(): void
    {
        $indexer1 = $this->createIndexer(IndexerException::createAsWarning('Warning 1'));
        $indexer2 = $this->createIndexer(new \LogicException('General failure 1'));
        $indexer3 = $this->createIndexer(new \LogicException('General failure 2'));
        $indexer4 = $this->createIndexer(IndexerException::createAsWarning('Warning 2'));

        $delegating = new DelegatingIndexer();
        $delegating->addIndexer($indexer1);
        $delegating->addIndexer($indexer2);
        $delegating->addIndexer($indexer3);
        $delegating->addIndexer($indexer4);

        try {
            $delegating->index($this->createMock(Document::class));
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\LogicException::class, $exception);
            $this->assertSame('General failure 1 | General failure 2', $exception->getMessage());
        }

        try {
            $delegating->delete($this->createMock(Document::class));
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\LogicException::class, $exception);
            $this->assertSame('General failure 1 | General failure 2', $exception->getMessage());
        }

        try {
            $delegating->clear();
        } catch (\Throwable $exception) {
            $this->assertInstanceOf(\LogicException::class, $exception);
            $this->assertSame('General failure 1 | General failure 2', $exception->getMessage());
        }
    }

    public function testWarningExceptionCollectionOnly(): void
    {
        $indexer1 = $this->createIndexer(IndexerException::createAsWarning('Warning 1'));
        $indexer2 = $this->createIndexer(IndexerException::createAsWarning('Warning 2'));

        $delegating = new DelegatingIndexer();
        $delegating->addIndexer($indexer1);
        $delegating->addIndexer($indexer2);

        try {
            $delegating->index($this->createMock(Document::class));
        } catch (IndexerException $exception) {
            $this->assertSame('Warning 1 | Warning 2', $exception->getMessage());
            $this->assertTrue($exception->isOnlyWarning());
        }

        try {
            $delegating->delete($this->createMock(Document::class));
        } catch (IndexerException $exception) {
            $this->assertSame('Warning 1 | Warning 2', $exception->getMessage());
            $this->assertTrue($exception->isOnlyWarning());
        }

        try {
            $delegating->clear();
        } catch (IndexerException $exception) {
            $this->assertSame('Warning 1 | Warning 2', $exception->getMessage());
            $this->assertTrue($exception->isOnlyWarning());
        }
    }

    private function createIndexer(\Throwable|null $indexerException = null): IndexerInterface
    {
        $indexer = $this->createMock(IndexerInterface::class);
        $invocationMocker = $indexer
            ->expects($this->once())
            ->method('index')
        ;
        $invocationMocker->with($this->isInstanceOf(Document::class));

        if ($indexerException) {
            $invocationMocker->willThrowException($indexerException);
        }

        $invocationMocker = $indexer
            ->expects($this->once())
            ->method('delete')
        ;
        $invocationMocker->with($this->isInstanceOf(Document::class));

        if ($indexerException) {
            $invocationMocker->willThrowException($indexerException);
        }

        $invocationMocker = $indexer
            ->expects($this->once())
            ->method('clear')
        ;

        if ($indexerException) {
            $invocationMocker->willThrowException($indexerException);
        }

        return $indexer;
    }
}
