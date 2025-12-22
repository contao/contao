<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\MessageHandler;

use Contao\CoreBundle\Crawl\Escargot\Factory;
use Contao\CoreBundle\Crawl\Monolog\CrawlCsvLogHandler;
use Contao\CoreBundle\Job\Job;
use Contao\CoreBundle\Job\Jobs;
use Contao\CoreBundle\Messenger\Message\CrawlMessage;
use Monolog\Handler\GroupHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class CrawlMessageHandler
{
    private readonly Filesystem $fs;

    public function __construct(
        private readonly Factory $factory,
        private readonly Jobs $jobs,
        private readonly MessageBusInterface $messageBus,
        private readonly string $projectDir,
        private readonly int $concurrency,
    ) {
        $this->fs = new Filesystem();
    }

    public function __invoke(CrawlMessage $message): void
    {
        $job = $this->jobs->getByUuid($message->getJobId());

        if (!$job || $job->isCompleted()) {
            return;
        }

        $job = $job->markPending();
        $this->jobs->persist($job);

        $baseUris = $this->factory->getCrawlUriCollection();
        $escargotJobId = $job->getMetadata()['escargotJobId'] ?? null;
        $queue = $this->factory->createLazyQueue();

        if (null === $escargotJobId) {
            $escargot = $this->factory->create(
                $baseUris,
                $queue,
                $message->subscribers,
                ['headers' => $message->headers],
            );

            $escargotJobId = $escargot->getJobId();
            $job = $job->withMetadata(['escargotJobId' => $escargotJobId]);
            $this->jobs->persist($job);
        } else {
            $escargot = $this->factory->createFromJobId(
                $escargotJobId,
                $queue,
                $message->subscribers,
                ['headers' => $message->headers],
            );
        }

        $escargot = $escargot
            ->withMaxDurationInSeconds(20) // This we can improve in the future. It's only needed in the "web" scope
            ->withConcurrency($this->concurrency)
            ->withMaxDepth($message->maxDepth)
            ->withLogger($this->createLogger($job, $message->subscribers))
        ;

        $escargot->crawl();

        // Commit the result on the lazy queue
        $queue->commit($escargotJobId);
        $pending = $queue->countPending($escargotJobId);
        $all = $queue->countAll($escargotJobId);
        $finished = 0 === $pending;

        $job = $job->withProgressFromAmounts($pending, $all);
        $this->jobs->persist($job);

        if ($finished) {
            $this->finishJob($job, $message->subscribers);
            $queue->deleteJobId($escargotJobId);

            return;
        }

        // Not done yet, re-queue
        $this->messageBus->dispatch($message);
    }

    /**
     * Creates a logger that logs everything on debug level in a general debug log
     * file and everything above info level into a subscriber specific log file.
     *
     * @param array<string> $activeSubscribers
     */
    private function createLogger(Job $job, array $activeSubscribers): LoggerInterface
    {
        $handlers = [];

        // Create the general debug handler
        $debugHandler = new CrawlCsvLogHandler($this->getDebugLogPath($job), Level::Debug);
        $handlers[] = $debugHandler;

        // Create the subscriber specific info handlers
        foreach ($this->factory->getSubscribers($activeSubscribers) as $subscriber) {
            $subscriberHandler = new CrawlCsvLogHandler($this->getSubscriberLogFilePath($job, $subscriber->getName()), Level::Info);
            $subscriberHandler->setFilterSource($subscriber::class);
            $handlers[] = $subscriberHandler;
        }

        $groupHandler = new GroupHandler($handlers);

        $logger = new Logger('crawl-logger');
        $logger->pushHandler($groupHandler);

        return $logger;
    }

    private function getDebugLogPath(Job $job): string
    {
        return $this->getLogDir($job).'/_debug_log.csv';
    }

    private function getSubscriberLogFilePath(Job $job, string $subscriberName): string
    {
        return $this->getLogDir($job).'/'.$subscriberName.'_log.csv';
    }

    private function getLogDir(Job $job): string
    {
        $logDir = \sprintf('%s/%s/contao-crawl/%s', sys_get_temp_dir(), hash('xxh3', $this->projectDir), $job->getUuid());

        if (!is_dir($logDir)) {
            $this->fs->mkdir($logDir);
        }

        return $logDir;
    }

    /**
     * @param array<string> $activeSubscribers
     */
    private function finishJob(Job $job, array $activeSubscribers): void
    {
        if ($this->fs->exists($this->getDebugLogPath($job))) {
            $this->jobs->addAttachment($job, 'debug_log.csv', fopen($this->getDebugLogPath($job), 'r'));
        }

        foreach ($this->factory->getSubscribers($activeSubscribers) as $subscriber) {
            if (!$this->fs->exists($this->getSubscriberLogFilePath($job, $subscriber->getName()))) {
                continue;
            }

            $this->jobs->addAttachment($job, $subscriber->getName().'_log.csv', fopen(
                $this->getSubscriberLogFilePath($job, $subscriber->getName()), 'r'),
            );
        }

        // Now that we have attached the files to the job (and thus they have been copied
        // to the VFS) we can delete the temp log dir to clean up
        $this->fs->remove($this->getLogDir($job));

        $job = $job->markCompleted();
        $this->jobs->persist($job);
    }
}
