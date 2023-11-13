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

use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

class MessageListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    #[AsEventListener]
    public function onWorkerMessageFailed(WorkerMessageFailedEvent $event): void
    {
        if ($event->willRetry()) {
            return;
        }

        $this->logger->error(
            sprintf('Message "%s" failed. Reason: "%s"',
                \get_class($event->getEnvelope()->getMessage()),
                $event->getThrowable()->getMessage(),
            ),
            [
                'exception' => $event->getThrowable(),
                'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR),
            ],
        );
    }
}
