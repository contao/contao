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

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class MessageListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    #[AsEventListener]
    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $this->logger->error(
            sprintf(
                'Message "%s" failed. Reason: "%s"',
                $event->getEnvelope()->getMessage()::class,
                $event->getThrowable()->getMessage(),
            ),
            ['exception' => $event->getThrowable()],
        );
    }
}
