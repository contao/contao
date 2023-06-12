<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Crawl\Escargot\Subscriber;

use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Nyholm\Psr7\Uri;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Subscriber\ExceptionSubscriberInterface;
use Terminal42\Escargot\Subscriber\HtmlCrawlerSubscriber;
use Terminal42\Escargot\Subscriber\RobotsSubscriber;
use Terminal42\Escargot\Subscriber\SubscriberInterface;
use Terminal42\Escargot\Subscriber\Util;
use Terminal42\Escargot\SubscriberLoggerTrait;

class SearchIndexSubscriber implements EscargotSubscriberInterface, EscargotAwareInterface, ExceptionSubscriberInterface, LoggerAwareInterface
{
    use EscargotAwareTrait;
    use LoggerAwareTrait;
    use SubscriberLoggerTrait;

    final public const TAG_SKIP = 'skip-search-index';

    private array $stats = ['ok' => 0, 'warning' => 0, 'error' => 0];

    public function __construct(
        private readonly IndexerInterface $indexer,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getName(): string
    {
        return 'search-index';
    }

    public function shouldRequest(CrawlUri $crawlUri): string
    {
        if ($crawlUri->hasTag(self::TAG_SKIP)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Do not request because it was marked to be skipped using the data-skip-search-index attribute.'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Before we request this URI, we only know about the tag from the robots.txt
        if ($crawlUri->hasTag(RobotsSubscriber::TAG_NOINDEX)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Do not request because it was marked "noindex" in the robots.txt.'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Respect robots.txt info and nofollow metadata
        if (!Util::isAllowedToFollow($crawlUri, $this->escargot)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Do not request because the URI was disallowed to be followed by nofollow or robots.txt hints.'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Skip the links that have a "type" attribute which is not text/html
        if ($crawlUri->hasTag(HtmlCrawlerSubscriber::TAG_NO_TEXT_HTML_TYPE)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Do not request because when the crawl URI was found, the "type" attribute was present and did not contain "text/html".'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Skip links that do not belong to our base URI collection
        if (!$this->escargot->getBaseUris()->containsHost($crawlUri->getUri()->getHost())) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Did not index because it was not part of the base URI collection.'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Let's go otherwise!
        return SubscriberInterface::DECISION_POSITIVE;
    }

    public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): string
    {
        $statusCode = $response->getStatusCode();

        // We only care about successful responses
        if ($statusCode < 200 || $statusCode >= 300) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                sprintf(
                    'Did not index because according to the HTTP status code the response was not successful (%s).',
                    $response->getStatusCode()
                )
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Skip any redirected URLs that are now outside our base hosts (#4213)
        $actualHost = (new Uri($response->getInfo('url')))->getHost();

        if ($crawlUri->getUri()->getHost() !== $actualHost && !$this->escargot->getBaseUris()->containsHost($actualHost)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Did not index because it was not part of the base URI collection.'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // No HTML, no index
        if (!Util::isOfContentType($response, 'text/html')) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Did not index because the response did not contain a "text/html" Content-Type header.'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // At this point, we know about the X-Robots-Tag header
        if ($crawlUri->hasTag(RobotsSubscriber::TAG_NOINDEX)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Do not request because it was marked "noindex" in the "X-Robots-Tag" header.'
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Let's go otherwise!
        return SubscriberInterface::DECISION_POSITIVE;
    }

    public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
    {
        // At this point, we know about the <meta name="robots"> tag
        if ($crawlUri->hasTag(RobotsSubscriber::TAG_NOINDEX)) {
            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                'Do not request because it was marked "noindex" in the <meta name="robots"> HTML tag.'
            );

            return;
        }

        $document = new Document(
            $crawlUri->getUri(),
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getContent()
        );

        try {
            $this->indexer->index($document);
            ++$this->stats['ok'];

            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::INFO,
                'Forwarded to the search indexer. Was indexed successfully.'
            );
        } catch (IndexerException $e) {
            if ($e->isOnlyWarning()) {
                ++$this->stats['warning'];
            } else {
                ++$this->stats['error'];
            }

            $this->logWithCrawlUri(
                $crawlUri,
                LogLevel::DEBUG,
                sprintf('Forwarded to the search indexer. Did not index because of the following reason: %s', $e->getMessage())
            );
        }
    }

    public function getResult(SubscriberResult|null $previousResult = null): SubscriberResult
    {
        $stats = $this->stats;

        if (null !== $previousResult) {
            $stats['ok'] += $previousResult->getInfo('stats')['ok'];
            $stats['warning'] += $previousResult->getInfo('stats')['warning'];
            $stats['error'] += $previousResult->getInfo('stats')['error'];
        }

        $result = new SubscriberResult(
            0 === $stats['error'],
            $this->translator->trans('CRAWL.searchIndex.summary', [$stats['ok'], $stats['error']], 'contao_default')
        );

        if (0 !== $stats['warning']) {
            $result->setWarning($this->translator->trans('CRAWL.searchIndex.warning', [$stats['warning']], 'contao_default'));
        }

        $result->addInfo('stats', $stats);

        return $result;
    }

    public function onTransportException(CrawlUri $crawlUri, TransportExceptionInterface $exception, ResponseInterface $response): void
    {
        $this->logWarning($crawlUri, 'Could not request properly: '.$exception->getMessage());
    }

    public function onHttpException(CrawlUri $crawlUri, HttpExceptionInterface $exception, ResponseInterface $response, ChunkInterface $chunk): void
    {
        $this->logWarning($crawlUri, 'HTTP Status Code: '.$response->getStatusCode());
    }

    private function logWarning(CrawlUri $crawlUri, string $message): void
    {
        ++$this->stats['warning'];

        $this->logWithCrawlUri($crawlUri, LogLevel::DEBUG, sprintf('Broken link! %s.', $message));
    }
}
