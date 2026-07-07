<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration;

final class DatabaseMigrationEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly DatabaseMigrationEventType $type,
        private readonly array $payload = [],
        private readonly DatabaseMigrationDecision|null $decision = null,
    ) {
    }

    public function getType(): DatabaseMigrationEventType
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getDecision(): DatabaseMigrationDecision|null
    {
        return $this->decision;
    }
}
