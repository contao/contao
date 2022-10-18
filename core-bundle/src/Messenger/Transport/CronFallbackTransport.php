<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\Transport;

use Contao\CoreBundle\Cron\Cron;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

class CronFallbackTransport implements TransportInterface
{
    public function __construct(private Cron $cron, private TransportInterface $target, private TransportInterface $fallback)
    {
    }

    public function get(): iterable
    {
        if ($this->cron->hasMinutelyCliCron()) {
            return $this->target->get();
        }

        return $this->fallback->get();
    }

    public function ack(Envelope $envelope): void
    {
        if ($this->cron->hasMinutelyCliCron()) {
            $this->target->ack($envelope);

            return;
        }

        $this->fallback->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        if ($this->cron->hasMinutelyCliCron()) {
            $this->target->reject($envelope);

            return;
        }

        $this->fallback->reject($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        if ($this->cron->hasMinutelyCliCron()) {
            return $this->target->send($envelope);
        }

        return $this->fallback->send($envelope);
    }
}
