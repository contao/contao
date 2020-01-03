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

use Contao\CoreBundle\Search\Escargot\Subscriber\BrokenLinkCheckerSubscriber;
use Contao\CoreBundle\Search\Escargot\Subscriber\SubscriberResult;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpClient\Chunk\LastChunk;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\BaseUriCollection;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Queue\InMemoryQueue;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class BrokenLinkCheckerSubscriberTest extends TestCase
{
    public function testName(): void
    {
        $subscriber = new BrokenLinkCheckerSubscriber();

        $this->assertSame('broken-link-checker', $subscriber->getName());
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
                ->with($expectedLogLevel, $expectedLogMessage, ['source' => BrokenLinkCheckerSubscriber::class])
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

        $subscriber = new BrokenLinkCheckerSubscriber();
        $subscriber->setEscargot($escargot);

        $decision = $subscriber->shouldRequest($crawlUri);

        $this->assertSame($expectedDecision, $decision);
    }

    public function shouldRequestProvider(): \Generator
    {
        yield 'Test skips URIs that do not belong to our base URI collection' => [
            (new CrawlUri(new Uri('https://github.com'), 0)),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://github.com/ (Level: 0, Processed: no, Found on: root, Tags: none)] Did not check because it is not part of the base URI collection or was not found on one of that is.',
        ];

        yield 'Test skips URIs that were found on an URI that did not belong to our base URI collection' => [
            (new CrawlUri(new Uri('https://gitlab.com'), 1, false, new Uri('https://github.com'))),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::DEBUG,
            '[URI: https://gitlab.com/ (Level: 1, Processed: no, Found on: https://github.com/, Tags: none)] Did not check because it is not part of the base URI collection or was not found on one of that is.',
            (new CrawlUri(new Uri('https://github.com'), 0, true)),
        ];

        yield 'Test requests if everything is okay' => [
            (new CrawlUri(new Uri('https://contao.org/foobar'), 0)),
            SubscriberInterface::DECISION_POSITIVE,
        ];
    }

    /**
     * @dataProvider needsContentProvider
     */
    public function testNeedsContent(ResponseInterface $response, string $expectedDecision, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with($expectedLogLevel, $expectedLogMessage, ['source' => BrokenLinkCheckerSubscriber::class])
            ;
        } else {
            $logger
                ->expects($this->never())
                ->method('log')
            ;
        }

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new BrokenLinkCheckerSubscriber();
        $subscriber->setEscargot($escargot);

        $decision = $subscriber->needsContent(
            new CrawlUri(new Uri('https://contao.org'), 0),
            $response,
            $this->createMock(ChunkInterface::class)
        );

        $this->assertSame($expectedDecision, $decision);

        $previousResult = null;

        if (0 !== \count($previousStats)) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public function needsContentProvider(): \Generator
    {
        yield 'Test reports responses that were not successful' => [
            $this->getResponse(404),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::ERROR,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 1],
        ];

        yield 'Test reports responses that were not successful (with previous stats)' => [
            $this->getResponse(404),
            SubscriberInterface::DECISION_NEGATIVE,
            LogLevel::ERROR,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 2],
            ['ok' => 0, 'error' => 1],
        ];

        yield 'Test does not report successful responses' => [
            $this->getResponse(),
            SubscriberInterface::DECISION_NEGATIVE,
            '',
            '',
            ['ok' => 1, 'error' => 0],
        ];

        yield 'Test does not report successful responses (with previous stats)' => [
            $this->getResponse(),
            SubscriberInterface::DECISION_NEGATIVE,
            '',
            '',
            ['ok' => 2, 'error' => 0],
            ['ok' => 1, 'error' => 0],
        ];
    }

    /**
     * @dataProvider onExceptionProvider
     */
    public function testOnException(ExceptionInterface $exception, ResponseInterface $response, ?ChunkInterface $chunk, string $expectedLogLevel, string $expectedLogMessage, array $expectedStats, array $previousStats = []): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        if ('' !== $expectedLogLevel) {
            $logger
                ->expects($this->once())
                ->method('log')
                ->with($expectedLogLevel, $expectedLogMessage, ['source' => BrokenLinkCheckerSubscriber::class])
            ;
        } else {
            $logger
                ->expects($this->never())
                ->method('log')
            ;
        }

        $escargot = Escargot::create(new BaseUriCollection([new Uri('https://contao.org')]), new InMemoryQueue());
        $escargot = $escargot->withLogger($logger);

        $subscriber = new BrokenLinkCheckerSubscriber();
        $subscriber->setEscargot($escargot);
        $subscriber->onException(new CrawlUri(new Uri('https://contao.org'), 0), $exception, $response, $chunk);

        $previousResult = null;

        if (0 !== \count($previousStats)) {
            $previousResult = new SubscriberResult(true, 'foobar');
            $previousResult->addInfo('stats', $previousStats);
        }

        $result = $subscriber->getResult($previousResult);
        $this->assertSame($expectedStats, $result->getInfo('stats'));
    }

    public function onExceptionProvider(): \Generator
    {
        yield 'Test reports responses that were not successful' => [
            new ClientException($this->getResponse(404)),
            $this->getResponse(404),
            new LastChunk(),
            LogLevel::ERROR,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 1],
        ];

        yield 'Test reports responses that were not successful (with previous result)' => [
            new ClientException($this->getResponse(404)),
            $this->getResponse(404),
            new LastChunk(),
            LogLevel::ERROR,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Broken link! HTTP Status Code: 404.',
            ['ok' => 0, 'error' => 2],
            ['ok' => 0, 'error' => 1],
        ];

        yield 'Test reports transport exception responses' => [
            new TransportException('Could not resolve host or timeout'),
            $this->getResponse(404),
            null,
            LogLevel::ERROR,
            '[URI: https://contao.org/ (Level: 0, Processed: no, Found on: root, Tags: none)] Broken link! Could not request properly: Could not resolve host or timeout.',
            ['ok' => 0, 'error' => 2],
            ['ok' => 0, 'error' => 1],
        ];
    }

    /**
     * @return ResponseInterface&MockObject
     */
    private function getResponse(int $statusCode = 200): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('getStatusCode')
            ->willReturn($statusCode)
        ;

        $response
            ->method('getInfo')
            ->willReturnCallback(
                static function (string $key) use ($statusCode) {
                    if ('http_code' === $key) {
                        return $statusCode;
                    }

                    if ('url' === $key) {
                        return '';
                    }

                    if ('response_headers' === $key) {
                        return [];
                    }

                    throw new \InvalidArgumentException('Invalid key: '.$key);
                }
            )
        ;

        return $response;
    }
}
