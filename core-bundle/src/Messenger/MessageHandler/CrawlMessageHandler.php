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
use Contao\CoreBundle\Job\Status;
use Contao\CoreBundle\Messenger\Message\CrawlMessage;
use Monolog\Handler\GroupHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class CrawlMessageHandler
{
    public function __construct(
        private readonly Factory $factory,
        private readonly Jobs $jobs,
        private readonly MessageBusInterface $messageBus,
        private string $projectDir,
        private int $concurrency,
    ) {
    }

    public function __invoke(CrawlMessage $message): void
    {
        $job = $this->jobs->getByUuid($message->jobUuid);

        // TODO: $jobs->isCompleted()
        if (null === $job || Status::completed === $job->getStatus()) {
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
            ->withMaxDurationInSeconds(20)
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

        $job = $job->withProgressFromAmounts($all, $pending);
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
        $debugHandler = new CrawlCsvLogHandler($this->getDebugLogPath($job), Logger::DEBUG);
        $handlers[] = $debugHandler;

        // Create the subscriber specific info handlers
        foreach ($this->factory->getSubscribers($activeSubscribers) as $subscriber) {
            $subscriberHandler = new CrawlCsvLogHandler($this->getSubscriberLogFilePath($job, $subscriber->getName()), Logger::INFO);
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
        return $this->getLogDir().'/'.$job->getUuid().'_log.csv';
    }

    private function getSubscriberLogFilePath(Job $job, string $subscriberName): string
    {
        return $this->getLogDir().'/'.$job->getUuid().'_'.$subscriberName.'_log.csv';
    }

    private function getLogDir(): string
    {
        $logDir = \sprintf('%s/%s/contao-crawl', sys_get_temp_dir(), hash('xxh3', $this->projectDir));

        if (!is_dir($logDir)) {
            (new Filesystem())->mkdir($logDir);
        }

        return $logDir;
    }

    /**
     * @param array<string> $activeSubscribers
     */
    private function finishJob(Job $job, array $activeSubscribers): void
    {
        if (file_exists($this->getDebugLogPath($job))) {
            $this->jobs->addAttachment($job, 'debug_log.csv', fopen($this->getDebugLogPath($job), 'r'));
        }

        foreach ($this->factory->getSubscribers($activeSubscribers) as $subscriber) {
            if (!file_exists($this->getSubscriberLogFilePath($job, $subscriber->getName()))) {
                continue;
            }

            $this->jobs->addAttachment($job, $subscriber->getName().'_log.csv', fopen(
                $this->getSubscriberLogFilePath($job, $subscriber->getName()), 'r'),
            );
        }

        $job = $job->markCompleted();
        $this->jobs->persist($job);
    }
}
