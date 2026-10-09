<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Webhook\MessageHandler;

use Contao\CoreBundle\Webhook\Message\ConsumeWebhookMessage;
use Contao\CoreBundle\Webhook\WebhookRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventHandler;
use Symfony\Component\RemoteEvent\Messenger\ConsumeRemoteEventMessage;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsMessageHandler]
final class ConsumeWebhookHandler
{
    public function __construct(
        private readonly WebhookRepository $repository,
        private readonly ConsumeRemoteEventHandler $consumer,
    ) {
    }

    public function __invoke(ConsumeWebhookMessage $message): void
    {
        if (!$this->repository->claimIncoming($message->eventId)) {
            return;
        }

        try {
            ($this->consumer)(new ConsumeRemoteEventMessage(
                $message->receiver,
                new RemoteEvent($message->name, $message->externalId, $message->payload),
            ));

            $this->repository->markIncomingProcessed($message->eventId);
        } catch (\Throwable $exception) {
            $this->repository->releaseIncoming($message->eventId);

            throw $exception;
        }
    }
}
