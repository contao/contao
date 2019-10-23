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
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use PHPUnit\Framework\TestCase;

class DelegatingIndexerTest extends TestCase
{
    public function testDelegatesTheMethodCalls(): void
    {
        $indexer1 = $this->createMock(IndexerInterface::class);
        $indexer1
            ->expects($this->once())
            ->method('index')
            ->with($this->isInstanceOf(Document::class))
        ;

        $indexer1
            ->expects($this->once())
            ->method('clear')
        ;

        $indexer2 = $this->createMock(IndexerInterface::class);
        $indexer2
            ->expects($this->once())
            ->method('index')
            ->with($this->isInstanceOf(Document::class))
        ;

        $indexer2
            ->expects($this->once())
            ->method('clear')
        ;

        $delegating = new DelegatingIndexer();
        $delegating->addIndexer($indexer1);
        $delegating->addIndexer($indexer2);
        $delegating->index($this->createMock(Document::class));
        $delegating->clear();
    }
}
