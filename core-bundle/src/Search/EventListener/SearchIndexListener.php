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
use Symfony\Component\Routing\RouterInterface;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Event\FinishedCrawlingEvent;
use Terminal42\Escargot\Event\SuccessfulResponseEvent;

class SearchIndexListener extends AbstractFileLoggingListener
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

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

    public function onSuccessfulResponse(SuccessfulResponseEvent $event): void
    {
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
        $this->initLogFile($event->getEscargot()->getJobId());
        $this->writeLogLinesToFile([
            'HTTP status code',
            'URI',
            'Level',
            'Found on',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SuccessfulResponseEvent::class => 'onSuccessfulResponse',
            FinishedCrawlingEvent::class => 'onFinishedCrawling',
        ];
    }

    public function getResultAsHtml(Escargot $escargot): string
    {
        $this->initLogFile($escargot->getJobId());

        return sprintf('<a href="%s">Download log as CSV!</a>', $this->getDownloadLink($escargot->getJobId()));
    }

    public function addResultToConsole(Escargot $escargot, OutputInterface $output): void
    {
        $this->initLogFile($escargot->getJobId());

        $output->writeln('Rebuilt search index!');
        $output->writeln('The log file can be found here: '.$this->logFile);
    }

    protected function getFileName(): string
    {
        return $this->getName().'.csv';
    }
}
