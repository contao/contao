<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Webhook\Message;

final readonly class DeliverWebhookMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public int $subscriptionId,
        public string $name,
        public string $externalId,
        public array $payload,
    ) {
    }
}
