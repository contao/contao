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
use Contao\CoreBundle\Search\Escargot\Subscriber\SubscriberResult;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Subscriber\HtmlCrawlerSubscriber;
use Terminal42\Escargot\Subscriber\RobotsSubscriber;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class SearchIndexSubscriberTest extends TestCase
{
    public function testName(): void
    {
        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class), $this->getTranslator());
        $this->assertSame('search-index', $subscriber->getName());
    }

    /**
     * @dataProvider shouldRequestProvider
     */
    public function testShouldRequest(CrawlUri $crawlUri, string $expectedDecision, string $expectedLogLevel = '', string $expectedLogMessage = '', CrawlUri $foundOnUri = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with($expectedLogLevel, $expectedLogMessage, ['source' => SearchIndexSubscriber::class])
            ;
        } else {
            $logger
                ->expects($this->never())
                ->method('log')
            ;
        }

        $queue = new InMemoryQueue();
        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), $queue);
        $escargot = $escargot->withLogger($logger);

        if ($foundOnUri) {
            $queue->add($escargot->getJobId(), $foundOnUri);
        }

        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class), $this->getTranslator());
        $subscriber->setEscargot($escargot);

        $decision = $subscriber->shouldRequest($crawlUri);

        $this->assertSame($expectedDecision, $decision);
    }

    public function shouldRequestProvider(): \Generator
    {
        yield 'Test skips URIs where the original URI contained a no-follow tag' => [
            (new CrawlUri(new Uri('https://contao.org'), 1, false, new Uri('https://original.contao.org'))),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://contao.org/ (Level: 1, Processed: no, Found on: https://original.contao.org/, Tags: none)] Do not request because when the crawl URI was found, the robots information disallowed following this URI.',
            (new CrawlUri(new Uri('https://original.contao.org'), 0, true))->addTag(RobotsSubscriber::TAG_NOFOLLOW),
        ];

        yield 'Test skips URIs that contained the rel-nofollow tag' => [
            (new CrawlUri(new Uri('https://contao.org'), 0))->addTag(HtmlCrawlerSubscriber::TAG_REL_NOFOLLOW),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: rel-nofollow)] Do not request because when the crawl URI was found, the "rel" attribute contained "nofollow".',
        ];

        yield 'Test skips URIs that contained the no-html-type tag' => [
            (new CrawlUri(new Uri('https://contao.org'), 0))->addTag(HtmlCrawlerSubscriber::TAG_NO_TEXT_HTML_TYPE),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: no-txt-html-type)] Do not request because when the crawl URI was found, the "type" attribute was present and did not contain "text/html".',
        ];

        yield 'Test skips URIs that do not belong to our base URI collection' => [
            (new CrawlUri(new Uri('https://github.com'), 0)),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://github.com/ (Level: 0, Processed: no, Found on: root, Tags: none)] Did not index because it was not part of the base URI collection.',
        ];

        yield 'Test requests if everything is okay' => [
            (new CrawlUri(new Uri('https://contao.org/foobar'), 0)),
            SubscriberInterface::DECISION_POSITIVE,
        ];
    }

    /**
     * @dataProvider needsContentProvider
     */
    public function testNeedsContent(ResponseInterface $response, string $expectedDecision, string $expectedLogLevel = '', string $expectedLogMessage = ''): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with($expectedLogLevel, $expectedLogMessage, ['source' => SearchIndexSubscriber::class])
            ;
        } else {
            $logger
                ->expects($this->never())
                ->method('log')
            ;
        }

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new SearchIndexSubscriber($this->createMock(IndexerInterface::class), $this->getTranslator());
        $subscriber->setEscargot($escargot);

        $decision = $subscriber->needsContent(
            new CrawlUri(new Uri('https://contao.org'), 0),
            $response,
            $this->createMock(ChunkInterface::class)
        );

        $this->assertSame($expectedDecision, $decision);
    }

    public function needsContentProvider(): \Generator
    {
        yield 'Test skips responses that were not successful' => [
            $this->getResponse(true, 404),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Did not index because according to the HTTP status code the response was not successful (404).',
        ];

        yield 'Test skips responses that were not HTML responses' => [
            $this->getResponse(false),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Did not index because the response did not contain a "text/html" Content-Type header.',
        ];

        yield 'Test requests successful HTML responses' => [
            $this->getResponse(true),
            SubscriberInterface::DECISION_POSITIVE,
        ];
    }

    /**
     * @dataProvider onLastChunkProvider
     */
    public function testOnLastChunk(?IndexerException $indexerException, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with($expectedLogLevel, $expectedLogMessage, ['source' => SearchIndexSubscriber::class])
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

        $subscriber = new SearchIndexSubscriber($indexer, $this->getTranslator());
        $subscriber->setEscargot($escargot);

        $subscriber->onLastChunk(
            new CrawlUri(new Uri('https://contao.org'), 0),
            $this->getResponse(true),
            $this->createMock(ChunkInterface::class)
        );

        $previousResult = null;

        if (0 !== \count($previousStats)) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public function onLastChunkProvider(): \Generator
    {
        yield 'Successful index' => [
            null,
            LogLevel::INFO,
            'Sent https://contao.org/ to the search indexer. Was indexed successfully.',
            ['ok' => 1, 'warning' => 0, 'error' => 0],
        ];

        yield 'Successful index with previous result' => [
            null,
            LogLevel::INFO,
            'Sent https://contao.org/ to the search indexer. Was indexed successfully.',
            ['ok' => 2, 'warning' => 0, 'error' => 0],
            ['ok' => 1, 'warning' => 0, 'error' => 0],
        ];

        yield 'Unsuccessful index (warning only)' => [
            IndexerException::createAsWarning('Warning!'),
            LogLevel::DEBUG,
            'Sent https://contao.org/ to the search indexer. Did not index because of the following reason: Warning!',
            ['ok' => 0, 'warning' => 1, 'error' => 0],
        ];

        yield 'Unsuccessful index (warning only) with previous result' => [
            IndexerException::createAsWarning('Warning!'),
            LogLevel::DEBUG,
            'Sent https://contao.org/ to the search indexer. Did not index because of the following reason: Warning!',
            ['ok' => 0, 'warning' => 11, 'error' => 0],
            ['ok' => 0, 'warning' => 10, 'error' => 0],
        ];

        yield 'Unsuccessful index' => [
            new IndexerException('Major failure!'),
            LogLevel::DEBUG,
            'Sent https://contao.org/ to the search indexer. Did not index because of the following reason: Major failure!',
            ['ok' => 0, 'warning' => 0, 'error' => 1],
        ];

        yield 'Unsuccessful index with previous result' => [
            new IndexerException('Major failure!'),
            LogLevel::DEBUG,
            'Sent https://contao.org/ to the search indexer. Did not index because of the following reason: Major failure!',
            ['ok' => 0, 'warning' => 0, 'error' => 2],
            ['ok' => 0, 'warning' => 0, 'error' => 1],
        ];
    }

    private function getResponse(bool $asHtml, int $statusCode = 200): ResponseInterface
    {
        $headers = $asHtml ? ['content-type' => ['text/html']] : [];

        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getHeaders')
            ->willReturn($headers)
        ;

        $response
            ->method('getStatusCode')
            ->willReturn($statusCode)
        ;

        return $response;
    }

    /**
     * @return TranslatorInterface&MockObject
     */
    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('Foobar')
        ;

        return $translator;
    }
}
