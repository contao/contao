<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger\EventListener;

use Contao\CoreBundle\Messenger\AutoFallbackNotifier;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

class WorkerListener
{
    public function __construct(private AutoFallbackNotifier $autoFallbackNotifier)
    {
    }

    #[AsEventListener]
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            $this->autoFallbackNotifier->ping($transportName);
        }
    }

    #[AsEventListener]
    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            $this->autoFallbackNotifier->ping($transportName);
        }
    }
}
