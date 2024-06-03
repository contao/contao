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

use Contao\CoreBundle\Messenger\WebWorker;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Cache\CacheItemPoolInterface;
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

class WebWorkerTest extends TestCase
{
    private EventDispatcher $eventDispatcher;

    private ConsumeMessagesCommand $command;

    private Logger $logger;

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
            ->with('contao-web-worker-transport-1')
        ;

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            ['transport-1'],
        );
        $this->addEventsToEventDispatcher($webWorker);
        $this->triggerWorkerOnKernelTerminate();
    }

    public function testWorkerIsStoppedIfIdle(): void
    {
        $cache = $this->createCache(); // No real workers running

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            ['transport-1'],
        );
        $this->addEventsToEventDispatcher($webWorker);
        $this->triggerWorkerOnKernelTerminate();

        // This test would run for 30 seconds if it failed. If the worker is correctly
        // stopped, it will return immediately and log "Stopping worker.".
        $this->assertLoggerContainsMessage('Stopping worker.');
    }

    private function createCache(array $transportsWithRunningWorkers = []): CacheItemPoolInterface
    {
        $cache = new ArrayAdapter();

        foreach ($transportsWithRunningWorkers as $transport) {
            $item = $cache->getItem('contao-web-worker-'.$transport);
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

    private function addEventsToEventDispatcher(WebWorker $webWorker): void
    {
        $this->eventDispatcher->addListener(
            WorkerStartedEvent::class,
            static function (WorkerStartedEvent $event) use ($webWorker): void {
                $webWorker->onWorkerStarted($event);
            },
        );
        $this->eventDispatcher->addListener(
            WorkerRunningEvent::class,
            static function (WorkerRunningEvent $event) use ($webWorker): void {
                $webWorker->onWorkerRunning($event);
            },
        );
        $this->eventDispatcher->addListener(
            TerminateEvent::class,
            static function (TerminateEvent $event) use ($webWorker): void {
                $webWorker->onKernelTerminate($event);
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
