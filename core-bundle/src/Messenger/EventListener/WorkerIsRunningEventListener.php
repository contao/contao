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

#[AsEventListener]
class WorkerIsRunningEventListener
{
    public function __construct(private AutoFallbackNotifier $autoFallbackNotifier)
    {
    }

    public function __invoke(WorkerRunningEvent $event): void
    {
        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            $this->autoFallbackNotifier->ping($transportName);
        }
    }
}
