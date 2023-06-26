<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Crawl\Escargot\Subscriber;

use Contao\CoreBundle\Crawl\Escargot\Subscriber\SearchIndexSubscriber;
use Contao\CoreBundle\Crawl\Escargot\Subscriber\SubscriberResult;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\Chunk\LastChunk;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Subscriber\HtmlCrawlerSubscriber;
use Terminal42\Escargot\Subscriber\RobotsSubscriber;
use Terminal42\Escargot\Subscriber\SubscriberInterface;
use Terminal42\Escargot\SubscriberLogger;

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
    public function testShouldRequest(CrawlUri $crawlUri, string $expectedDecision, string $expectedLogLevel = '', string $expectedLogMessage = '', CrawlUri|null $foundOnUri = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with(
                    $expectedLogLevel,
                    $expectedLogMessage,
                    $this->callback(
                        function (array $context) {
                            $this->assertInstanceOf(CrawlUri::class, $context['crawlUri']);
                            $this->assertSame(SearchIndexSubscriber::class, $context['source']);

                            return true;
                        }
                    )
                )
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
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));

        $decision = $subscriber->shouldRequest($crawlUri);

        $this->assertSame($expectedDecision, $decision);
    }

    public function shouldRequestProvider(): \Generator
    {
        yield 'Test skips URIs where the original URI contained a robots.txt no-follow tag' => [
            new CrawlUri(new Uri('https://contao.org'), 1, false, new Uri('https://original.contao.org')),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Do not request because the URI was disallowed to be followed by nofollow or robots.txt hints.',
            (new CrawlUri(new Uri('https://original.contao.org'), 0, true))->addTag(RobotsSubscriber::TAG_NOFOLLOW),
        ];

        yield 'Test skips URIs where the original URI was marked "noindex" in the robots.txt' => [
            (new CrawlUri(new Uri('https://contao.org'), 0))->addTag(RobotsSubscriber::TAG_NOINDEX),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Do not request because it was marked "noindex" in the robots.txt.',
        ];

        yield 'Test skips URIs that were disallowed by the robots.txt content' => [
            (new CrawlUri(new Uri('https://contao.org'), 0))->addTag(RobotsSubscriber::TAG_DISALLOWED_ROBOTS_TXT),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Do not request because the URI was disallowed to be followed by nofollow or robots.txt hints.',
        ];

        yield 'Test skips URIs that contained the no-html-type tag' => [
            (new CrawlUri(new Uri('https://contao.org'), 0))->addTag(HtmlCrawlerSubscriber::TAG_NO_TEXT_HTML_TYPE),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Do not request because when the crawl URI was found, the "type" attribute was present and did not contain "text/html".',
        ];

        yield 'Test skips URIs that do not belong to our base URI collection' => [
            new CrawlUri(new Uri('https://github.com'), 0),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Did not index because it was not part of the base URI collection.',
        ];

        yield 'Test skips URIs that were marked to be skipped by the data attribue' => [
            (new CrawlUri(new Uri('https://contao.org/foobar'), 0))->addTag(SearchIndexSubscriber::TAG_SKIP),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Do not request because it was marked to be skipped using the data-skip-search-index attribute.',
        ];

        yield 'Test requests if everything is okay' => [
            new CrawlUri(new Uri('https://contao.org/foobar'), 0),
            SubscriberInterface::DECISION_POSITIVE,
        ];
    }

    /**
     * @dataProvider needsContentProvider
     */
    public function testNeedsContent(ResponseInterface $response, string $expectedDecision, string $expectedLogLevel = '', string $expectedLogMessage = '', CrawlUri|null $crawlUri = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with(
                    $expectedLogLevel,
                    $expectedLogMessage,
                    $this->callback(
                        function (array $context) {
                            $this->assertInstanceOf(CrawlUri::class, $context['crawlUri']);
                            $this->assertSame(SearchIndexSubscriber::class, $context['source']);

                            return true;
                        }
                    )
                )
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
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));

        $decision = $subscriber->needsContent(
            $crawlUri ?? new CrawlUri(new Uri('https://contao.org'), 0),
            $response,
            $this->createMock(ChunkInterface::class)
        );

        $this->assertSame($expectedDecision, $decision);
    }

    public function needsContentProvider(): \Generator
    {
        yield 'Test skips responses that were not successful' => [
            $this->mockResponse(true, 404),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Did not index because according to the HTTP status code the response was not successful (404).',
        ];

        yield 'Test skips responses that were not HTML responses' => [
            $this->mockResponse(false),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Did not index because the response did not contain a "text/html" Content-Type header.',
        ];

        yield 'Test requests successful HTML responses' => [
            $this->mockResponse(true),
            SubscriberInterface::DECISION_POSITIVE,
        ];

        yield 'Test skips redirected responses outside the target domain' => [
            $this->mockResponse(false, 200, 'https://example.com'),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Did not index because it was not part of the base URI collection.',
        ];

        yield 'Test skips URIs where the "X-Robots-Tag" header contains "noindex"' => [
            $this->mockResponse(true),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Do not request because it was marked "noindex" in the "X-Robots-Tag" header.',
            (new CrawlUri(new Uri('https://contao.org'), 0))->addTag(RobotsSubscriber::TAG_NOINDEX),
        ];
    }

    /**
     * @dataProvider onLastChunkProvider
     */
    public function testOnLastChunk(IndexerException|null $indexerException, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = [], CrawlUri|null $crawlUri = null): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $expectedLogLevel,
                $expectedLogMessage,
                $this->callback(
                    function (array $context) {
                        $this->assertInstanceOf(CrawlUri::class, $context['crawlUri']);
                        $this->assertSame(SearchIndexSubscriber::class, $context['source']);

                        return true;
                    }
                )
            )
        ;

        $indexer = $this->createMock(IndexerInterface::class);

        if (null === $indexerException) {
            $indexer
                ->expects(['ok' => 0, 'warning' => 0, 'error' => 0] === $expectedStats ? $this->never() : $this->once())
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
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));

        $subscriber->onLastChunk(
            $crawlUri ?? new CrawlUri(new Uri('https://contao.org'), 0),
            $this->mockResponse(true),
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
        yield 'Test skips URIs where the "X-Robots-Tag" header contains "noindex"' => [
            null,
            LogLevel::DEBUG,
            'Do not request because it was marked "noindex" in the <meta name="robots"> HTML tag.',
            ['ok' => 0, 'warning' => 0, 'error' => 0],
            [],
            (new CrawlUri(new Uri('https://contao.org'), 0))->addTag(RobotsSubscriber::TAG_NOINDEX),
        ];

        yield 'Successful index' => [
            null,
            LogLevel::INFO,
            'Forwarded to the search indexer. Was indexed successfully.',
            ['ok' => 1, 'warning' => 0, 'error' => 0],
        ];

        yield 'Successful index with previous result' => [
            null,
            LogLevel::INFO,
            'Forwarded to the search indexer. Was indexed successfully.',
            ['ok' => 2, 'warning' => 0, 'error' => 0],
            ['ok' => 1, 'warning' => 0, 'error' => 0],
        ];

        yield 'Unsuccessful index (warning only)' => [
            IndexerException::createAsWarning('Warning!'),
            LogLevel::DEBUG,
            'Forwarded to the search indexer. Did not index because of the following reason: Warning!',
            ['ok' => 0, 'warning' => 1, 'error' => 0],
        ];

        yield 'Unsuccessful index (warning only) with previous result' => [
            IndexerException::createAsWarning('Warning!'),
            LogLevel::DEBUG,
            'Forwarded to the search indexer. Did not index because of the following reason: Warning!',
            ['ok' => 0, 'warning' => 11, 'error' => 0],
            ['ok' => 0, 'warning' => 10, 'error' => 0],
        ];

        yield 'Unsuccessful index' => [
            new IndexerException('Major failure!'),
            LogLevel::DEBUG,
            'Forwarded to the search indexer. Did not index because of the following reason: Major failure!',
            ['ok' => 0, 'warning' => 0, 'error' => 1],
        ];

        yield 'Unsuccessful index with previous result' => [
            new IndexerException('Major failure!'),
            LogLevel::DEBUG,
            'Forwarded to the search indexer. Did not index because of the following reason: Major failure!',
            ['ok' => 0, 'warning' => 0, 'error' => 2],
            ['ok' => 0, 'warning' => 0, 'error' => 1],
        ];
    }

    /**
     * @dataProvider onTransportExceptionProvider
     */
    public function testOnTransportException(TransportException $exception, ResponseInterface $response, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with(
                    $expectedLogLevel,
                    $expectedLogMessage,
                    $this->callback(
                        function (array $context) {
                            $this->assertInstanceOf(CrawlUri::class, $context['crawlUri']);
                            $this->assertSame(SearchIndexSubscriber::class, $context['source']);

                            return true;
                        }
                    )
                )
            ;
        } else {
            $logger
                ->expects($this->never())
                ->method('log')
            ;
        }

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->never())
            ->method('index')
        ;

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new SearchIndexSubscriber($indexer, $this->getTranslator());
        $subscriber->setEscargot($escargot);
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));
        $subscriber->onTransportException(new CrawlUri(new Uri('https://contao.org'), 0), $exception, $response);

        $previousResult = null;

        if (0 !== \count($previousStats)) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public function onTransportExceptionProvider(): \Generator
    {
        yield 'Test reports transport exception responses' => [
            new TransportException('Could not resolve host or timeout'),
            $this->mockResponse(true, 404),
            LogLevel::DEBUG,
            'Broken link! Could not request properly: Could not resolve host or timeout.',
            ['ok' => 0, 'warning' => 2, 'error' => 0],
            ['ok' => 0, 'warning' => 1, 'error' => 0],
        ];
    }

    /**
     * @dataProvider onHttpExceptionProvider
     */
    public function testOnHttpException(HttpExceptionInterface $exception, ResponseInterface $response, ChunkInterface $chunk, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with(
                    $expectedLogLevel,
                    $expectedLogMessage,
                    $this->callback(
                        function (array $context) {
                            $this->assertInstanceOf(CrawlUri::class, $context['crawlUri']);
                            $this->assertSame(SearchIndexSubscriber::class, $context['source']);

                            return true;
                        }
                    )
                )
            ;
        } else {
            $logger
                ->expects($this->never())
                ->method('log')
            ;
        }

        $indexer = $this->createMock(IndexerInterface::class);
        $indexer
            ->expects($this->never())
            ->method('index')
        ;

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new SearchIndexSubscriber($indexer, $this->getTranslator());
        $subscriber->setEscargot($escargot);
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));
        $subscriber->onHttpException(new CrawlUri(new Uri('https://contao.org'), 0), $exception, $response, $chunk);

        $previousResult = null;

        if (0 !== \count($previousStats)) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public function onHttpExceptionProvider(): \Generator
    {
        yield 'Test reports responses that were not successful' => [
            new ClientException($this->mockResponse(true, 404)),
            $this->mockResponse(true, 404),
            new LastChunk(),
            LogLevel::DEBUG,
            'Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'warning' => 1, 'error' => 0],
        ];

        yield 'Test reports responses that were not successful (with previous result)' => [
            new ClientException($this->mockResponse(true, 404)),
            $this->mockResponse(true, 404),
            new LastChunk(),
            LogLevel::DEBUG,
            'Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'warning' => 2, 'error' => 0],
            ['ok' => 0, 'warning' => 1, 'error' => 0],
        ];
    }

    private function mockResponse(bool $asHtml, int $statusCode = 200, string $url = 'https://contao.org'): ResponseInterface
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

        $response
            ->method('getInfo')
            ->willReturnCallback(
                static function (string $key) use ($statusCode, $url) {
                    return match ($key) {
                        'http_code' => $statusCode,
                        'url' => $url,
                        'response_headers' => [],
                        default => null,
                    };
                }
            )
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
