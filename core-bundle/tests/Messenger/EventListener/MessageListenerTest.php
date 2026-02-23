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

use Contao\CoreBundle\Messenger\EventListener\MessageListener;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Worker;

class MessageListenerTest extends TestCase
{
    public function testIgnoresMessageIfGoingToRetry(): void
    {
        $event = new WorkerMessageFailedEvent(new Envelope(new \stdClass()), 'receiver', new \RuntimeException('error!'));
        $event->setForRetry();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('error')
        ;

        $listener = new MessageListener($logger, $this->createStub(Connection::class));
        $listener->onWorkerMessageFailed($event);
    }

    public function testLogsError(): void
    {
        $event = new WorkerMessageFailedEvent(new Envelope(new \stdClass()), 'receiver', new \RuntimeException('error!'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Message "stdClass" failed: "error!"')
        ;

        $listener = new MessageListener($logger, $this->createStub(Connection::class));
        $listener->onWorkerMessageFailed($event);
    }

    public function testClosesDoctrineConnectionWhenIdle(): void
    {
        $event = new WorkerRunningEvent($this->createStub(Worker::class), true);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('close')
        ;

        $listener = new MessageListener($this->createStub(LoggerInterface::class), $connection);
        $listener->onWorkerRunning($event);
    }

    public function testDoesNotCloseDoctrineConnectionWhenNotIdle(): void
    {
        $event = new WorkerRunningEvent($this->createStub(Worker::class), false);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('close')
        ;

        $listener = new MessageListener($this->createStub(LoggerInterface::class), $connection);
        $listener->onWorkerRunning($event);
    }
}
