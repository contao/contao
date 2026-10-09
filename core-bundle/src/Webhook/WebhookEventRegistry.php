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

final class WebhookEventRegistry
{
    /**
     * @param array<class-string<WebhookEventInterface>, array{name: string, provider?: string, properties: array}> $events
     */
    public function __construct(private readonly array $events = [])
    {
    }

    /**
     * @return array{name: string, provider?: string, properties: array}|null
     */
    public function get(string $class): array|null
    {
        return $this->events[$class] ?? null;
    }

    /**
     * @return array<class-string<WebhookEventInterface>, array{name: string, provider?: string, properties: array}>
     */
    public function all(): array
    {
        return $this->events;
    }
}
