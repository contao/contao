<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Escargot\Subscriber;

use Contao\CoreBundle\Search\Escargot\Subscriber\SearchIndexSubscriber;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class SearchIndexSubscriberTest extends TestCase
{
    public function testName(): void
    {
        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class));
        $this->assertSame('search-index', $subscriber->getName());
    }

    public function testDoesNotInterfereWithDecisionOnShouldRequest(): void
    {
        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class));
        $decision = $subscriber->shouldRequest(
            $this->getCrawlUri(),
            SubscriberInterface::DECISION_ABSTAIN
        );

        $this->assertSame(SubscriberInterface::DECISION_ABSTAIN, $decision);
    }

    public function testOnlyRequestsContentIfTheResponseContainedHtml(): void
    {
        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class));
        $decision = $subscriber->needsContent(
            $this->getCrawlUri(),
            $this->getResponse(false),
            $this->createMock(ChunkInterface::class),
            SubscriberInterface::DECISION_ABSTAIN
        );

        $this->assertSame(SubscriberInterface::DECISION_ABSTAIN, $decision);

        $decision = $subscriber->needsContent(
            $this->getCrawlUri(),
            $this->getResponse(true),
            $this->createMock(ChunkInterface::class),
            SubscriberInterface::DECISION_ABSTAIN
        );

        $this->assertSame(SubscriberInterface::DECISION_POSITIVE, $decision);
    }

    public function testIgnoresIrrelevantHosts(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::DEBUG,
                '[URI: https://example.com/ (Level: 0, Processed: no, Found on: root, Tags: none)] Did not index because it was not part of the base URI collection.',
                ['source' => SearchIndexSubscriber::class]
            )
        ;

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class));
        $subscriber->setEscargot($escargot);

        $subscriber->onLastChunk(
            $this->getCrawlUri('https://example.com'),
            $this->getResponse(true),
            $this->createMock(ChunkInterface::class)
        );
    }

    public function testIgnoresNonHtmlResponses(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::DEBUG,
                '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Did not index because it was not an HTML response.',
                ['source' => SearchIndexSubscriber::class]
            )
        ;

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class));
        $subscriber->setEscargot($escargot);

        $subscriber->onLastChunk(
            $this->getCrawlUri(),
            $this->getResponse(false),
            $this->createMock(ChunkInterface::class)
        );
    }

    /**
     * @dataProvider passesOnDocumentToIndexerCorrectlyProvider
     */
    public function testPassesOnDocumentToIndexerCorrectly(IndexerException $indexerException = null, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $expectedLogLevel,
                $expectedLogMessage,
                ['source' => SearchIndexSubscriber::class]
            )
        ;

        $indexer = $this->createMock(IndexerInterface::class);

        if (null === $indexerException) {
            $indexer
                ->expects($this->once())
                ->method('index')
            ;
        } else {
            $indexer
                ->expects($this->once())
                ->method('index')
                ->willThrowException($indexerException)
            ;
        }

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new SearchIndexSubscriber($indexer);
        $subscriber->setEscargot($escargot);

        $subscriber->onLastChunk(
            $this->getCrawlUri(),
            $this->getResponse(true),
            $this->createMock(ChunkInterface::class)
        );

        $result = $subscriber->getResult();
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public function passesOnDocumentToIndexerCorrectlyProvider(): \Generator
    {
        yield 'Successful index' => [
            null,
            LogLevel::INFO,
            'Sent https://contao.org/ to the search indexer. Was indexed successfully.',
            ['ok' => 1, 'warning' => 0, 'error' => 0],
        ];

        yield 'Unsuccessful index (warning only)' => [
            IndexerException::createAsWarning('Warning'),
            LogLevel::DEBUG,
            'Sent https://contao.org/ to the search indexer. Did not index because of the following reason: Warning.',
            ['ok' => 0, 'warning' => 1, 'error' => 0],
        ];

        yield 'Unsuccessful index' => [
            new IndexerException('Major failure!'),
            LogLevel::DEBUG,
            'Sent https://contao.org/ to the search indexer. Did not index because of the following reason: Major failure!.',
            ['ok' => 0, 'warning' => 0, 'error' => 1],
        ];
    }

    private function getCrawlUri(string $uri = 'https://contao.org'): CrawlUri
    {
        return new CrawlUri(new Uri($uri), 0);
    }

    private function getResponse(bool $asHtml): ResponseInterface
    {
        $headers = $asHtml ? ['content-type' => ['text/html']] : [];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers)
        ;

        return $response;
    }
}
