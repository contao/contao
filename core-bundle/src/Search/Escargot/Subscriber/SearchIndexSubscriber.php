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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class SearchIndexSubscriber implements EscargotSubscriber, EscargotAwareInterface
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

    public function shouldRequest(CrawlUri $crawlUri, string $currentDecision): string
    {
        // We do not interfere with any decision in between. We only want to index the data that
        // is coming in. Other subscribers decide whether it's requested or not.
        return $currentDecision;
    }

    public function needsContent(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk, string $currentDecision): string
    {
        // So some other subscriber asked for the request. Great, now we want to make sure the whole
        // content is downloaded if it's HTML.
        if ($this->isHtml($response)) {
            return SubscriberInterface::DECISION_POSITIVE;
        }

        // If it's not HTML, we do not interfere with any decision taken so far.
        return $currentDecision;
    }

    public function onLastChunk(CrawlUri $crawlUri, ResponseInterface $response, ChunkInterface $chunk): void
    {
        // Only index URIs of the same host
        if (!$this->escargot->getBaseUris()->containsHost($crawlUri->getUri()->getHost())) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage('Did not index because it was not part of the base URI collection.'),
                ['source' => \get_class($this)]
            );

            return;
        }

        // Only index HTML
        if (!$this->isHtml($response)) {
            $this->escargot->log(
                LogLevel::DEBUG,
                $crawlUri->createLogMessage('Did not index because it was not an HTML response.'),
                ['source' => \get_class($this)]
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
                sprintf('Sent %s to the search indexer. Did not index because of the following reason: %s.', (string) $crawlUri->getUri(), $e->getMessage()),
                ['source' => \get_class($this)]
            );
        }
    }

    public function getResult(SubscriberResult $previousResult = null): SubscriberResult
    {
        $stats = $this->stats;

        if (null !== $previousResult) {
            $stats = array_merge($previousResult->getInfo('stats'));
        }

        $result = new SubscriberResult(
            0 === $stats['error'],
            sprintf('Indexed %d URI(s) successfully. %d failed.', $stats['ok'], $stats['error']),
        );

        if (0 !== $stats['warning']) {
            $result->setWarning(sprintf('%d URI(s) were skipped (if you are missing one, checkout the debug logs).', $stats['warning']));
        }

        $result->addInfo('stats', $stats);

        return $result;
    }

    private function isHtml(ResponseInterface $response): bool
    {
        if (!\in_array('content-type', array_keys($response->getHeaders()), true)) {
            return false;
        }

        return false !== strpos($response->getHeaders()['content-type'][0], 'text/html');
    }
}
