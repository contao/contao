<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Table(name: 'webhook_event')]
#[Entity]
#[Index(name: 'processing_state', columns: ['processingState'])]
#[UniqueConstraint(name: 'endpoint_event', columns: ['endpointId', 'externalId'])]
final class WebhookEvent
{
    #[Id]
    #[Column(type: 'integer', options: ['unsigned' => true])]
    #[GeneratedValue]
    private int $id = 0;

    #[Column(type: 'integer', options: ['unsigned' => true])]
    private int $endpointId = 0;

    #[Column(type: 'string', length: 255)]
    private string $externalId = '';

    #[Column(type: 'string', length: 16)]
    private string $processingState = 'pending';

    #[Column(type: 'datetime_immutable', nullable: true)]
    private \DateTimeImmutable|null $processingStartedAt = null;

    public function __construct(int $endpointId, string $externalId)
    {
        $this->endpointId = $endpointId;
        $this->externalId = $externalId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProcessingState(): string
    {
        return $this->processingState;
    }

    public function getEndpointId(): int
    {
        return $this->endpointId;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function markProcessing(\DateTimeImmutable $startedAt): void
    {
        $this->processingState = 'processing';
        $this->processingStartedAt = $startedAt;
    }

    public function markPending(): void
    {
        $this->processingState = 'pending';
        $this->processingStartedAt = null;
    }

    public function markProcessed(): void
    {
        $this->processingState = 'processed';
        $this->processingStartedAt = null;
    }

    public function isProcessingExpired(\DateTimeImmutable $now, int $leaseSeconds): bool
    {
        return 'processing' === $this->processingState
            && $this->processingStartedAt
            && $this->processingStartedAt <= $now->modify('-'.$leaseSeconds.' seconds');
    }
}
