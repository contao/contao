<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\EventListener;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;

class MessageListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Connection $connection,
    ) {
    }

    #[AsEventListener]
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        if (!$event->isWorkerIdle()) {
            return;
        }

        // Close the database connection when the worker is idle (see #8199)
        $this->connection->close();
    }

    #[AsEventListener]
    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $exception = $event->getThrowable();

        $this->logger->error(
            \sprintf('Message "%s" failed: "%s"', $event->getEnvelope()->getMessage()::class, $exception->getMessage()),
            ['exception' => $exception],
        );
    }
}
