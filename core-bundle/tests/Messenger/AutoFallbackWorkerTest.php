<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Messenger;

use Contao\CoreBundle\Messenger\AutoFallbackWorker;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Log\Logger;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\RoutableMessageBus;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;

class AutoFallbackWorkerTest extends TestCase
{
    private EventDispatcher $eventDispatcher;

    private ConsumeMessagesCommand $command;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->logger = new Logger(LogLevel::DEBUG, null, null, new RequestStack(), true);
        $this->createConsumeCommand();
    }

    public function testPingOnlyAboutConfiguredTransports(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->once()) // Tests that transport-2 is not called
            ->method('getItem')
            ->with('auto-fallback-worker-transport-1')
        ;

        $fallbackWorker = new AutoFallbackWorker(
            $cache,
            $this->command,
            ['transport-1'],
        );
        $this->addEventsToEventDispatcher($fallbackWorker);
        $this->triggerWorkerOnKernelTerminate();
    }

    public function testWorkerIsStoppedIfIdle(): void
    {
        $cache = $this->createCache(); // No real workers running

        $fallbackWorker = new AutoFallbackWorker(
            $cache,
            $this->command,
            ['transport-1'],
        );
        $this->addEventsToEventDispatcher($fallbackWorker);
        $this->triggerWorkerOnKernelTerminate();

        // This test would run for 30 seconds if it failed. If the worker is correctly
        // stopped, it will return immediately and log "Stopping worker.".
        $this->assertLoggerContainsMessage('Stopping worker.');
    }

    private function createCache(array $transportsWithRunningWorkers = [])
    {
        $cache = new ArrayAdapter();

        foreach ($transportsWithRunningWorkers as $transport) {
            $item = $cache->getItem('auto-fallback-worker-'.$transport);
            $item->expiresAfter(60);
            $cache->save($item);
        }

        return $cache;
    }

    private function triggerWorkerOnKernelTerminate(): void
    {
        $this->eventDispatcher->dispatch(new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            new Response(),
        ));
    }

    private function createConsumeCommand(): void
    {
        $receiverLocator = new Container();
        $receiverLocator->set('transport-1', $this->createMock(ReceiverInterface::class));
        $receiverLocator->set('transport-2', $this->createMock(ReceiverInterface::class));
        $receiverLocator->set('transport-3', $this->createMock(ReceiverInterface::class));

        $this->command = new ConsumeMessagesCommand(
            $this->createMock(RoutableMessageBus::class),
            $receiverLocator,
            $this->eventDispatcher,
            $this->logger,
        );
    }

    private function addEventsToEventDispatcher(AutoFallbackWorker $fallbackWorker): void
    {
        $this->eventDispatcher->addListener(
            WorkerStartedEvent::class,
            static function (WorkerStartedEvent $event) use ($fallbackWorker): void {
                $fallbackWorker->onWorkerStarted($event);
            },
        );
        $this->eventDispatcher->addListener(
            WorkerRunningEvent::class,
            static function (WorkerRunningEvent $event) use ($fallbackWorker): void {
                $fallbackWorker->onWorkerRunning($event);
            },
        );
        $this->eventDispatcher->addListener(
            TerminateEvent::class,
            static function (TerminateEvent $event) use ($fallbackWorker): void {
                $fallbackWorker->onKernelTerminate($event);
            },
        );
    }

    private function assertLoggerContainsMessage(string $message): void
    {
        foreach ($this->logger->getLogs() as $log) {
            if ($log['message'] === $message) {
                $this->addToAssertionCount(1);
                break;
            }
        }
    }
}
