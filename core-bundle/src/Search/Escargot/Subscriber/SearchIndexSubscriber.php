<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\Escargot\Subscriber;

use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerException;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Psr\Log\LogLevel;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Subscriber\HtmlCrawlerSubscriber;
use Terminal42\Escargot\Subscriber\RobotsSubscriber;
use Terminal42\Escargot\Subscriber\SubscriberInterface;
use Terminal42\Escargot\Subscriber\Util;

class SearchIndexSubscriber implements EscargotSubscriberInterface, EscargotAwareInterface
{
    use EscargotAwareTrait;

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var array
     */
    private $stats = ['ok' => 0, 'warning' => 0, 'error' => 0];

    public function __construct(IndexerInterface $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'search-index';
    }

    public function shouldRequest(CrawlUri $crawlUri): string
    {
        // Check the original crawlUri to see if that one contained nofollow information
        if (
            null !== $crawlUri->getFoundOn()
            && ($originalCrawlUri = $this->escargot->getCrawlUri($crawlUri->getFoundOn()))
            && $originalCrawlUri->hasTag(RobotsSubscriber::TAG_NOFOLLOW)
        ) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage('Do not request because when the crawl URI was found, the robots information disallowed following this URI.'),
                ['source' => \get_class($this)]
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Skip rel="nofollow" links
        if ($crawlUri->hasTag(HtmlCrawlerSubscriber::TAG_REL_NOFOLLOW)) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage('Do not request because when the crawl URI was found, the "rel" attribute contained "nofollow".'),
                ['source' => \get_class($this)]
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Skip the links that have the "type" attribute set and it's not text/html
        if ($crawlUri->hasTag(HtmlCrawlerSubscriber::TAG_NO_TEXT_HTML_TYPE)) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage('Do not request because when the crawl URI was found, the "type" attribute was present and did not contain "text/html".'),
                ['source' => \get_class($this)]
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Skip links that do not belong to our base URI collection
        if (!$this->escargot->getBaseUris()->containsHost($crawlUri->getUri()->getHost())) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage('Did not index because it was not part of the base URI collection.'),
                ['source' => \get_class($this)]
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Let's go otherwise!
        return SubscriberInterface::DECISION_POSITIVE;
    }

    public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): string
    {
        // We only care about successful responses
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage(sprintf(
                    'Did not index because according to the HTTP status code the response was not successful (%s).',
                    $response->getStatusCode()
                )),
                ['source' => \get_class($this)]
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // No HTML, no index
        if (!Util::isOfContentType($response, 'text/html')) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage(
                    'Did not index because the response did not contain a "text/html" Content-Type header.'
                ),
                ['source' => \get_class($this)]
            );

            return SubscriberInterface::DECISION_NEGATIVE;
        }

        // Let's go otherwise!
        return SubscriberInterface::DECISION_POSITIVE;
    }

    public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
    {
        $document = new Document(
            $crawlUri->getUri(),
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getContent()
        );

        try {
            $this->indexer->index($document);
            ++$this->stats['ok'];

            $this->escargot->log(
                LogLevel::INFO,
                sprintf('Sent %s to the search indexer. Was indexed successfully.', (string) $crawlUri->getUri()),
                ['source' => \get_class($this)]
            );
        } catch (IndexerException $e) {
            if ($e->isOnlyWarning()) {
                ++$this->stats['warning'];
            } else {
                ++$this->stats['error'];
            }

            $this->escargot->log(
                LogLevel::DEBUG,
                sprintf('Sent %s to the search indexer. Did not index because of the following reason: %s', (string) $crawlUri->getUri(), $e->getMessage()),
                ['source' => \get_class($this)]
            );
        }
    }

    public function getResult(SubscriberResult $previousResult = null): SubscriberResult
    {
        $stats = $this->stats;

        if (null !== $previousResult) {
            $stats['ok'] += $previousResult->getInfo('stats')['ok'];
            $stats['warning'] += $previousResult->getInfo('stats')['warning'];
            $stats['error'] += $previousResult->getInfo('stats')['error'];
        }

        $result = new SubscriberResult(
            0 === $stats['error'],
            sprintf('Indexed %d URI(s) successfully. %d failed.', $stats['ok'], $stats['error'])
        );

        if (0 !== $stats['warning']) {
            $result->setWarning(sprintf('%d URI(s) were skipped (if you are missing one, checkout the debug logs).', $stats['warning']));
        }

        $result->addInfo('stats', $stats);

        return $result;
    }
}
