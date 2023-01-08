<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger\EventListener;

use Contao\CoreBundle\Messenger\AutoFallbackNotifier;
use Contao\CoreBundle\Messenger\EventListener\WorkerListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\WorkerMetadata;

class WorkerListenerTest extends TestCase
{
    public function testPingsCorrectlyOnRunning(): void
    {
        $worker = $this->mockWorker();
        $notifier = $this->mockNotifier();

        $event = new WorkerRunningEvent($worker, false);

        $listener = new WorkerListener($notifier);
        $listener->onWorkerRunning($event);
    }

    public function testPingsCorrectlyOnStart(): void
    {
        $worker = $this->mockWorker();
        $notifier = $this->mockNotifier();

        $event = new WorkerStartedEvent($worker);

        $listener = new WorkerListener($notifier);
        $listener->onWorkerStarted($event);
    }

    private function mockWorker(): Worker
    {
        $worker = $this->createMock(Worker::class);
        $worker
            ->expects($this->once())
            ->method('getMetadata')
            ->willReturn(new WorkerMetadata(['transportNames' => ['foo', 'bar']]))
        ;

        return $worker;
    }

    private function mockNotifier(): AutoFallbackNotifier
    {
        $notifier = $this->createMock(AutoFallbackNotifier::class);
        $notifier
            ->expects($this->exactly(2))
            ->method('ping')
            ->withConsecutive(
                ['foo'],
                ['bar'],
            )
        ;

        return $notifier;
    }
}
