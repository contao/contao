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
use Contao\CoreBundle\Messenger\EventListener\WorkerIsRunningEventListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Messenger\WorkerMetadata;

class WorkerIsRunningEventListenerTest extends TestCase
{
    public function testPingsCorrectly(): void
    {
        $worker = $this->createMock(Worker::class);
        $worker
            ->expects($this->once())
            ->method('getMetadata')
            ->willReturn(new WorkerMetadata(['transportNames' => ['foo', 'bar']]))
        ;

        $notifier = $this->createMock(AutoFallbackNotifier::class);
        $notifier
            ->expects($this->exactly(2))
            ->method('ping')
            ->withConsecutive(
                ['foo'],
                ['bar'],
            )
        ;

        $event = new WorkerRunningEvent($worker, false);

        $listener = new WorkerIsRunningEventListener($notifier);
        $listener($event);
    }
}
