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

use Contao\CoreBundle\Crawl\Escargot\Subscriber\BrokenLinkCheckerSubscriber;
use Contao\CoreBundle\Crawl\Escargot\Subscriber\SubscriberResult;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\Chunk\LastChunk;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Subscriber\SubscriberInterface;
use Terminal42\Escargot\SubscriberLogger;

class BrokenLinkCheckerSubscriberTest extends TestCase
{
    public function testName(): void
    {
        $subscriber = new BrokenLinkCheckerSubscriber($this->mockTranslator());

        $this->assertSame('broken-link-checker', $subscriber->getName());
    }

    #[DataProvider('shouldRequestProvider')]
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
                            $this->assertSame(BrokenLinkCheckerSubscriber::class, $context['source']);

                            return true;
                        },
                    ),
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

        $subscriber = new BrokenLinkCheckerSubscriber($this->mockTranslator());
        $subscriber->setEscargot($escargot);
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));

        $decision = $subscriber->shouldRequest($crawlUri);

        $this->assertSame($expectedDecision, $decision);
    }

    public static function shouldRequestProvider(): iterable
    {
        yield 'Test skips URIs that do not belong to our base URI collection' => [
            new CrawlUri(new Uri('https://github.com'), 0),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Did not check because it is not part of the base URI collection or was not found on one of that is.',
        ];

        yield 'Test skips URIs that were found on an URI that did not belong to our base URI collection' => [
            new CrawlUri(new Uri('https://gitlab.com'), 1, false, new Uri('https://github.com')),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Did not check because it is not part of the base URI collection or was not found on one of that is.',
            new CrawlUri(new Uri('https://github.com'), 0, true),
        ];

        yield 'Test skips URIs that were marked to be skipped by the data attribue' => [
            (new CrawlUri(new Uri('https://github.com/foobar'), 1, false, new Uri('https://github.com')))->addTag(BrokenLinkCheckerSubscriber::TAG_SKIP),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            'Did not check because it was marked to be skipped using the data-skip-broken-link-checker attribute.',
            new CrawlUri(new Uri('https://github.com'), 0, true),
        ];

        yield 'Test requests if everything is okay' => [
            new CrawlUri(new Uri('https://contao.org/foobar'), 0),
            SubscriberInterface::DECISION_POSITIVE,
        ];
    }

    #[DataProvider('needsContentProvider')]
    public function testNeedsContent(CrawlUri $crawlUri, array $responseArguments, string $expectedDecision, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $response = $this->mockResponse(...$responseArguments);
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
                            $this->assertSame(BrokenLinkCheckerSubscriber::class, $context['source']);

                            return true;
                        },
                    ),
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

        $subscriber = new BrokenLinkCheckerSubscriber($this->mockTranslator());
        $subscriber->setEscargot($escargot);
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));

        $decision = $subscriber->needsContent($crawlUri, $response, $this->createStub(ChunkInterface::class));

        $this->assertSame($expectedDecision, $decision);

        $previousResult = null;

        if ($previousStats) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public static function needsContentProvider(): iterable
    {
        yield 'Test reports responses that were not successful' => [
            new CrawlUri(new Uri('https://contao.org'), 0),
            [404],
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::ERROR,
            'Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 1],
        ];

        yield 'Test reports responses that were not successful (with previous stats)' => [
            new CrawlUri(new Uri('https://contao.org'), 0),
            [404],
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::ERROR,
            'Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 2],
            ['ok' => 0, 'error' => 1],
        ];

        yield 'Test does report successful responses' => [
            new CrawlUri(new Uri('https://contao.org'), 0),
            [],
            SubscriberInterface::DECISION_POSITIVE,
            '',
            '',
            ['ok' => 1, 'error' => 0],
        ];

        yield 'Test does not report successful responses if url not in base collection' => [
            new CrawlUri(new Uri('https://github.com'), 0),
            [200, 'https://github.com'],
            SubscriberInterface::DECISION_NEGATIVE,
            '',
            '',
            ['ok' => 1, 'error' => 0],
        ];

        yield 'Test does not report successful responses (with previous stats)' => [
            new CrawlUri(new Uri('https://contao.org'), 0),
            [],
            SubscriberInterface::DECISION_POSITIVE,
            '',
            '',
            ['ok' => 2, 'error' => 0],
            ['ok' => 1, 'error' => 0],
        ];

        yield 'Test does not report redirected responses outside the target domain' => [
            new CrawlUri(new Uri('https://contao.org'), 0),
            [200, 'https://example.com'],
            SubscriberInterface::DECISION_NEGATIVE,
            '',
            '',
            ['ok' => 1, 'error' => 0],
        ];
    }

    #[DataProvider('onTransportExceptionProvider')]
    public function testOnTransportException(TransportException $exception, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $response = $this->mockResponse(404);
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
                            $this->assertSame(BrokenLinkCheckerSubscriber::class, $context['source']);

                            return true;
                        },
                    ),
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

        $subscriber = new BrokenLinkCheckerSubscriber($this->mockTranslator());
        $subscriber->setEscargot($escargot);
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));
        $subscriber->onTransportException(new CrawlUri(new Uri('https://contao.org'), 0), $exception, $response);

        $previousResult = null;

        if ($previousStats) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public static function onTransportExceptionProvider(): iterable
    {
        yield 'Test reports transport exception responses' => [
            new TransportException('Could not resolve host or timeout'),
            LogLevel::ERROR,
            'Broken link! Could not request properly: Could not resolve host or timeout.',
            ['ok' => 0, 'error' => 2],
            ['ok' => 0, 'error' => 1],
        ];
    }

    #[DataProvider('onHttpExceptionProvider')]
    public function testOnHttpException(ChunkInterface $chunk, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $response = $this->mockResponse(404);
        $exception = new ClientException($response);

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
                            $this->assertSame(BrokenLinkCheckerSubscriber::class, $context['source']);

                            return true;
                        },
                    ),
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

        $subscriber = new BrokenLinkCheckerSubscriber($this->mockTranslator());
        $subscriber->setEscargot($escargot);
        $subscriber->setLogger(new SubscriberLogger($logger, $subscriber::class));
        $subscriber->onHttpException(new CrawlUri(new Uri('https://contao.org'), 0), $exception, $response, $chunk);

        $previousResult = null;

        if ($previousStats) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public static function onHttpExceptionProvider(): iterable
    {
        yield 'Test reports responses that were not successful' => [
            new LastChunk(),
            LogLevel::ERROR,
            'Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 1],
        ];

        yield 'Test reports responses that were not successful (with previous result)' => [
            new LastChunk(),
            LogLevel::ERROR,
            'Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 2],
            ['ok' => 0, 'error' => 1],
        ];
    }

    private function mockResponse(int $statusCode = 200, string $url = 'https://contao.org'): ResponseInterface&Stub
    {
        $response = $this->createStub(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn($statusCode)
        ;

        $response
            ->method('getInfo')
            ->willReturnCallback(
                static function (string $key) use ($statusCode, $url) {
                    if ('http_code' === $key) {
                        return $statusCode;
                    }

                    if ('url' === $key) {
                        return $url;
                    }

                    if ('response_headers' === $key) {
                        return [];
                    }

                    throw new \InvalidArgumentException('Invalid key: '.$key);
                },
            )
        ;

        return $response;
    }

    private function mockTranslator(): TranslatorInterface&Stub
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturn('Foobar')
        ;

        return $translator;
    }
}
