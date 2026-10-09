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

interface WebhookEventInterface
{
    public function getId(): string;

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array;
}
