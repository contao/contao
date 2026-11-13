<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\WebhooksBundle\Tests\Entity;

use Contao\TestCase\ContaoTestCase;
use Contao\WebhooksBundle\Entity\WebhookEvent;

final class WebhookEventTest extends ContaoTestCase
{
    public function testProcessingLeaseExpires(): void
    {
        $event = new WebhookEvent(1, 'event-id');
        $event->markProcessing(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $this->assertTrue($event->isProcessingExpired(new \DateTimeImmutable('2026-01-01 13:00:01'), 3600));
        $this->assertFalse($event->isProcessingExpired(new \DateTimeImmutable('2026-01-01 12:59:59'), 3600));
    }

    public function testCompletedProcessingLeaseDoesNotExpire(): void
    {
        $event = new WebhookEvent(1, 'event-id');
        $event->markProcessing(new \DateTimeImmutable('2026-01-01 12:00:00'));
        $event->markProcessed();

        $this->assertFalse($event->isProcessingExpired(new \DateTimeImmutable('2026-01-01 13:00:01'), 3600));
    }
}
