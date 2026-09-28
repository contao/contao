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

final readonly class IncomingWebhookContext
{
    public function __construct(
        public int $endpointId,
        public string $receiverName,
        public int $eventId,
    ) {
    }
}
