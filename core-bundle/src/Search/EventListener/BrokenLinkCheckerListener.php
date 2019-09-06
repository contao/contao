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

use Symfony\Component\Console\Output\OutputInterface;
use Terminal42\Escargot\Escargot;
use Terminal42\Escargot\Event\ExcludedByUriFilterEvent;
use Terminal42\Escargot\Event\FinishedCrawlingEvent;
use Terminal42\Escargot\Event\SuccessfulResponseEvent;
use Terminal42\Escargot\Event\UnsuccessfulResponseEvent;

class BrokenLinkCheckerListener extends AbstractFileLoggingListener
{
    public function getName(): string
    {
        return 'broken-link-checker';
    }

    public function getResultAsHtml(Escargot $escargot): string
    {
        $this->initLogFile($escargot->getJobId());

        return sprintf('<a href="%s">Download log as CSV!</a>', $this->getDownloadLink($escargot->getJobId()));
    }

    public function addResultToConsole(Escargot $escargot, OutputInterface $output): void
    {
        $this->initLogFile($escargot->getJobId());

        $output->writeln('Checked for broken links!');
        $output->writeln('The log file can be found here: '.$this->logFile);
    }


    protected function getFileName(): string
    {
        return $this->getName() . 'csv';
    }

    public function onFinishedCrawling(FinishedCrawlingEvent $event): void
    {
        $this->initLogFile($event->getEscargot()->getJobId());
        $this->writeLogLinesToFile([
            'HTTP status code',
            'URI',
            'Level',
            'Found on',
            'Skipped',
            'Skip reason',
        ]);
    }

    public function onUnsuccessfulResponse(UnsuccessfulResponseEvent $event): void
    {
        $response = $event->getResponse();

        $this->logLines[] = [
            $response->getStatusCode(),
            (string) $event->getCrawlUri()->getUri(),
            $event->getCrawlUri()->getLevel(),
            (string) $event->getCrawlUri()->getFoundOn(),
            'yes',
            'Wrong HTTP status code'
        ];
    }

    public function onExcludedByUriFilter(ExcludedByUriFilterEvent $event): void
    {
        // TODO:
    }


    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            UnsuccessfulResponseEvent::class => 'onUnsuccessfulResponse',
            ExcludedByUriFilterEvent::class => 'onExcludedByUriFilter',
        ];
    }
}
