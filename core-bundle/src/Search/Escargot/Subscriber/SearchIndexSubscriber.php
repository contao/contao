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
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Terminal42\Escargot\CrawlUri;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\EscargotAwareInterface;
use Terminal42\Escargot\EscargotAwareTrait;
use Terminal42\Escargot\Subscriber\FinishedCrawlingSubscriberInterface;
use Terminal42\Escargot\Subscriber\SubscriberInterface;

class SearchIndexSubscriber extends AbstractFileHandlingSubscriber implements EscargotAwareInterface, FinishedCrawlingSubscriberInterface, ControllerResultProvidingSubscriberInterface
{
    use EscargotAwareTrait;

    private const LOG_FILENAME = 'log.csv';

    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var array
     */
    private $logLines = [];

    public function __construct(IndexerInterface $indexer, RouterInterface $router, Filesystem $filesystem = null)
    {
        $this->indexer = $indexer;

        parent::__construct($router, $filesystem);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'search-index';
    }

    public function finishedCrawling(): void
    {
        $this->initFile($this->escargot->getJobId(), self::LOG_FILENAME);

        $this->writeCsv(
            $this->escargot->getJobId(),
            self::LOG_FILENAME,
            [
                'HTTP status code',
                'URI',
                'Level',
                'Found on',
            ],
            $this->logLines
        );
    }

    public function getResultAsHtml(Escargot $escargot): string
    {
        return sprintf('<a href="%s">TODO: Download log as CSV!</a>', $this->generateControllerLink($escargot->getJobId(), [
            'file' => 'log',
        ]));
    }

    public function addResultToConsole(Escargot $escargot, OutputInterface $output): void
    {
        $file = $this->initFile($escargot->getJobId(), self::LOG_FILENAME);

        $output->writeln('Rebuilt search index!');
        $output->writeln('The log file can be found here: '.$file);
    }

    public function controllerAction(Request $request, string $jobId): Response
    {
        if ('log' !== $request->query->get('file')) {
            return new Response('Not Found', Response::HTTP_NOT_FOUND);
        }

        return $this->createBinaryFileResponseForDownload($jobId, self::LOG_FILENAME);
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
        $this->logLines[] = [
            $response->getStatusCode(),
            (string) $crawlUri->getUri(),
            $crawlUri->getLevel(),
            (string) $crawlUri->getFoundOn(),
        ];

        $document = new Document(
            $crawlUri->getUri(),
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getContent()
        );

        $this->indexer->index($document);
    }

    private function isHtml(ResponseInterface $response): bool
    {
        if (!\in_array('content-type', array_keys($response->getHeaders()), true)) {
            return false;
        }

        return false !== strpos($response->getHeaders()['content-type'][0], 'text/html');
    }
}
