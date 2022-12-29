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
use Symfony\Component\Messenger\Transport\TransportInterface;

class AutoFallbackTransport implements TransportInterface
{
    public function __construct(private AutoFallbackNotifier $autoFallbackNotifier, private string $targetTransportName, private TransportInterface $target, private TransportInterface $fallback)
    {
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

    public function getTargetTransportName(): string
    {
        return $this->targetTransportName;
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
        return $this->autoFallbackNotifier->isWorkerRunning($this->targetTransportName);
    }
}
