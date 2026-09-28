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

use Contao\CoreBundle\Entity\WebhookEvent;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class WebhookRepository
{
    private const PROCESSING_LEASE_SECONDS = 3600;

    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findEnabledReceiver(string $token): array|null
    {
        return $this->connection->fetchAssociative('SELECT id, token, receiver, secret FROM tl_webhook_ingoing WHERE token = ? AND enabled = 1', [$token]) ?: null;
    }

    public function getPublicToken(int $id): string|null
    {
        $token = $this->connection->fetchOne('SELECT token FROM tl_webhook_ingoing WHERE id = ?', [$id]);

        return false === $token ? null : (string) $token;
    }

    public function storeIncoming(int $endpointId, string $externalId): int|null
    {
        if ('' === $externalId) {
            throw new \InvalidArgumentException('Incoming webhook event IDs must not be empty.');
        }

        $webhookEvent = new WebhookEvent($endpointId, $externalId);

        try {
            $this->entityManager->persist($webhookEvent);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        return $webhookEvent->getId();
    }

    public function releaseIncoming(int $eventId): void
    {
        $event = $this->entityManager->getRepository(WebhookEvent::class)->findOneBy(['id' => $eventId]);

        if (null !== $event && 'processing' === $event->getProcessingState()) {
            $event->markPending();
            $this->entityManager->flush();
        }
    }

    public function deleteIncoming(int $eventId): void
    {
        $event = $this->entityManager->getRepository(WebhookEvent::class)->findOneBy(['id' => $eventId]);

        if (null !== $event) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
        }
    }

    /**
     * @return array{id: string, url: string|null, secret: string|null, enabled: bool}|null
     */
    public function getOutgoingSubscriptionById(int $id): array|null
    {
        $subscription = $this->connection->fetchAssociative('SELECT id, url, secret, enabled FROM tl_webhook_outgoing WHERE id = ?', [$id]);

        if (false === $subscription) {
            return null;
        }

        return [
            'id' => (string) $subscription['id'],
            'url' => null === $subscription['url'] ? null : (string) $subscription['url'],
            'secret' => null === $subscription['secret'] ? null : (string) $subscription['secret'],
            'enabled' => (bool) $subscription['enabled'],
        ];
    }

    /**
     * @return list<int>
     */
    public function getOutgoingSubscriptions(string $name): array
    {
        $subscriptions = [];

        foreach ($this->connection->fetchAllAssociative('SELECT id, events FROM tl_webhook_outgoing WHERE enabled = 1') as $subscription) {
            $events = \is_array($subscription['events']) ? $subscription['events'] : StringUtil::deserialize($subscription['events'], true);

            if (\in_array($name, $events, true)) {
                $subscriptions[] = (int) $subscription['id'];
            }
        }

        return $subscriptions;
    }

    public function getOutgoingSubscription(int $id): array|null
    {
        return $this->connection->fetchAssociative('SELECT id, events, enabled FROM tl_webhook_outgoing WHERE id = ?', [$id]) ?: null;
    }

    public function markIncomingProcessed(int $eventId): void
    {
        $event = $this->entityManager->getRepository(WebhookEvent::class)->findOneBy(['id' => $eventId]);

        if (null !== $event && 'processing' === $event->getProcessingState()) {
            $event->markProcessed();
            $this->entityManager->flush();
        }
    }

    public function claimIncoming(int $eventId): bool
    {
        return $this->entityManager->wrapInTransaction(
            function () use ($eventId): bool {
                $event = $this->entityManager->getRepository(WebhookEvent::class)->findOneBy(['id' => $eventId]);

                if (null !== $event) {
                    $this->entityManager->lock($event, LockMode::PESSIMISTIC_WRITE);
                }

                $now = new \DateTimeImmutable();
                $state = $event?->getProcessingState();

                if (null === $event || ('pending' !== $state && !('processing' === $state && $event->isProcessingExpired($now, self::PROCESSING_LEASE_SECONDS)))) {
                    return false;
                }

                $event->markProcessing($now);
                $this->entityManager->flush();

                return true;
            },
        );
    }
}
