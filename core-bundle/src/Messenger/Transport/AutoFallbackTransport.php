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

use Contao\CoreBundle\Messenger\AutoFallbackNotifier;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class AutoFallbackTransport implements TransportInterface, MessageCountAwareInterface
{
    public function __construct(
        private readonly AutoFallbackNotifier $autoFallbackNotifier,
        private readonly string $selfTransportName,
        private readonly TransportInterface $target,
        private readonly TransportInterface $fallback,
    ) {
    }

    public function get(): iterable
    {
        if ($this->isWorkerRunning()) {
            return $this->target->get();
        }

        return $this->fallback->get();
    }

    public function ack(Envelope $envelope): void
    {
        if ($this->isWorkerRunning()) {
            $this->target->ack($envelope);

            return;
        }

        $this->fallback->ack($envelope);
    }

    public function reject(Envelope $envelope): void
    {
        if ($this->isWorkerRunning()) {
            $this->target->reject($envelope);

            return;
        }

        $this->fallback->reject($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        if ($this->isWorkerRunning()) {
            return $this->target->send($envelope);
        }

        return $this->fallback->send($envelope);
    }

    public function getMessageCount(): int
    {
        $transport = $this->isWorkerRunning() ? $this->target : $this->fallback;

        if ($transport instanceof MessageCountAwareInterface) {
            return $transport->getMessageCount();
        }

        return 0;
    }

    public function getSelfTransportName(): string
    {
        return $this->selfTransportName;
    }

    public function getTarget(): TransportInterface
    {
        return $this->target;
    }

    public function getFallback(): TransportInterface
    {
        return $this->fallback;
    }

    private function isWorkerRunning(): bool
    {
        return $this->autoFallbackNotifier->isWorkerRunning($this->selfTransportName);
    }
}
