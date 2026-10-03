<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Webhook;

use Contao\CoreBundle\Webhook\Message\DeliverWebhookMessage;
use Symfony\Component\Messenger\MessageBusInterface;

final class WebhookDispatcher
{
    public function __construct(
        private readonly WebhookEventRegistry $registry,
        private readonly WebhookRepository $repository,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(WebhookEventInterface $event): void
    {
        $metadata = $this->registry->get($event::class);

        if (null === $metadata) {
            throw new \InvalidArgumentException(\sprintf('The webhook event "%s" is not registered.', $event::class));
        }

        $payload = $event->getPayload();

        try {
            json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \InvalidArgumentException('The webhook event payload must contain only JSON-compatible values.', previous: $exception);
        }

        foreach ($this->repository->getOutgoingSubscriptions($metadata['name']) as $subscriptionId) {
            $this->messageBus->dispatch(new DeliverWebhookMessage($subscriptionId, $metadata['name'], $event->getId(), $payload));
        }
    }
}
