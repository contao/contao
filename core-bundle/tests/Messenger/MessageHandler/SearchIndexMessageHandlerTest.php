<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\MessageHandler;

use Contao\CoreBundle\Messenger\Message\SearchIndexMessage;
use Contao\CoreBundle\Messenger\MessageHandler\SearchIndexMessageHandler;
use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class SearchIndexMessageHandlerTest extends TestCase
{
    public function testShouldIndex(): void
    {
        $message = SearchIndexMessage::createWithIndex(new Document(new Uri(), 200, [], ''));

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->once())
            ->method('index')
            ->with($message->getDocument())
        ;

        $handler = new SearchIndexMessageHandler($indexer);
        $handler($message);
    }

    public function testShouldDelete(): void
    {
        $message = SearchIndexMessage::createWithDelete(new Document(new Uri(), 200, [], ''));

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->once())
            ->method('delete')
            ->with($message->getDocument())
        ;

        $handler = new SearchIndexMessageHandler($indexer);
        $handler($message);
    }

    public function testIgnoresWarningOnlyIndexerExceptions(): void
    {
        $message = SearchIndexMessage::createWithIndex(new Document(new Uri(), 200, [], ''));

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->once())
            ->method('index')
            ->with($message->getDocument())
            ->willThrowException(IndexerException::createAsWarning('warning'))
        ;

        $handler = new SearchIndexMessageHandler($indexer);
        $handler($message);
    }

    public function testThrowsOnIndexerExceptions(): void
    {
        $message = SearchIndexMessage::createWithIndex(new Document(new Uri(), 200, [], ''));

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->once())
            ->method('index')
            ->with($message->getDocument())
            ->willThrowException(new IndexerException('exception'))
        ;

        $handler = new SearchIndexMessageHandler($indexer);

        $this->expectException(IndexerException::class);
        $this->expectExceptionMessage('exception');

        $handler($message);
    }
}
