<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Search\EventListener;

use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Event\FinishedCrawlingEvent;
use Terminal42\Escargot\Event\ResponseEvent;

class SearchIndexListener extends AbstractFileHandlingListener implements ControllerResultProvidingSubscriberInterface
{
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

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->getCurrentChunk()->isLast()) {
            return;
        }

        $response = $event->getResponse();
        $this->logLines[] = [
            $response->getStatusCode(),
            (string) $event->getCrawlUri()->getUri(),
            $event->getCrawlUri()->getLevel(),
            (string) $event->getCrawlUri()->getFoundOn(),
        ];

        $document = new Document(
            $event->getCrawlUri()->getUri(),
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getContent()
        );

        $this->indexer->index($document);
    }

    public function onFinishedCrawling(FinishedCrawlingEvent $event): void
    {
        $this->initFile($event->getEscargot()->getJobId(), self::LOG_FILENAME);

        $this->writeCsv(
            $event->getEscargot()->getJobId(),
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ResponseEvent::class => 'onResponse',
            FinishedCrawlingEvent::class => 'onFinishedCrawling',
        ];
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
}
