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

use Symfony\Component\RemoteEvent\RemoteEvent;

interface WebhookConsumerInterface
{
    public function consume(RemoteEvent $event, IncomingWebhookContext $context): void;
}
