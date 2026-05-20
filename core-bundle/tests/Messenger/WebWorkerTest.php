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
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\RoutableMessageBus;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;

class WebWorkerTest extends TestCase
{
    private EventDispatcher $eventDispatcher;

    private ConsumeMessagesCommand $command;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = new class() extends AbstractLogger {
            private array $logs = [];

            /**
             * @param string $message
             */
            public function log($level, $message, array $context = []): void
            {
                $this->logs[] = $message;
            }

            public function getLogs(): array
            {
                return $this->logs;
            }
        };

        $this->eventDispatcher = new EventDispatcher();
        $this->createConsumeCommand();
    }

    public function testPingOnlyAboutConfiguredTransports(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->atLeastOnce()) // Tests that transport-2 is not called
            ->method('getItem')
            ->with('contao-web-worker-transport-1')
        ;

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            $this->mockScopeMatcher(),
            ['transport-1'],
        );

        $this->addEventsToEventDispatcher($webWorker);
        $this->triggerRealWorkers(['transport-1', 'transport-2']);
    }

    public function testWorkerIsStoppedIfIdle(): void
    {
        $cache = new ArrayAdapter(); // No real workers running

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            $this->mockScopeMatcher(),
            ['transport-1'],
        );

        $this->addEventsToEventDispatcher($webWorker);
        $this->triggerWebWorker();

        // This test would run for 30 seconds if it failed. If the worker is correctly
        // stopped, it will return immediately and log "Stopping worker.".
        // @phpstan-ignore method.notFound
        $this->assertContains('Stopping worker.', $this->logger->getLogs());
    }

    public function testWorkerAlsoRunsInBackendScope(): void
    {
        $cache = new ArrayAdapter(); // No real workers running

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            $this->mockScopeMatcher(),
            ['transport-1'],
        );

        $this->addEventsToEventDispatcher($webWorker);

        $request = new Request();
        $request->attributes->set('_scope', 'backend');

        $this->eventDispatcher->dispatch(new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            new Response(),
        ));

        // @phpstan-ignore method.notFound
        $this->assertContains('Stopping worker.', $this->logger->getLogs());
    }

    public function testDoesNotRunWebWorkerForNonContaoMainRequestsWithoutOptInOrDispatchedMessages(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->expects($this->never())
            ->method('getItem')
        ;

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            $this->mockScopeMatcher(),
            ['transport-1'],
        );

        $this->addEventsToEventDispatcher($webWorker);

        $request = new Request();

        $this->eventDispatcher->dispatch(new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            new Response(),
        ));
    }

    public function testRunsWebWorkerForNonContaoMainRequestsIfExplicitlyEnabled(): void
    {
        $cache = new ArrayAdapter(); // No real workers running

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            $this->mockScopeMatcher(),
            ['transport-1'],
        );

        $this->addEventsToEventDispatcher($webWorker);

        $request = new Request();
        $request->attributes->set(WebWorker::REQUEST_ATTRIBUTE_ENABLE, true);

        $this->eventDispatcher->dispatch(new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            new Response(),
        ));

        // @phpstan-ignore method.notFound
        $this->assertContains('Stopping worker.', $this->logger->getLogs());
    }

    public function testRunsWebWorkerForNonContaoMainRequestsIfMessagesWereDispatched(): void
    {
        $cache = new ArrayAdapter(); // No real workers running

        $webWorker = new WebWorker(
            $cache,
            $this->command,
            $this->mockScopeMatcher(),
            ['transport-1'],
        );

        $this->addEventsToEventDispatcher($webWorker);

        $this->eventDispatcher->dispatch(new SendMessageToTransportsEvent(new Envelope(new \stdClass()), ['transport-1' => $this->createMock(SenderInterface::class)]));

        $this->eventDispatcher->dispatch(new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            new Response(),
        ));

        // @phpstan-ignore method.notFound
        $this->assertContains('Stopping worker.', $this->logger->getLogs());
    }

    public function testLimitsFallbackConsumptionToDispatchedMessagesPerTransport(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->method('isHit')
            ->willReturn(false)
        ;

        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache
            ->method('getItem')
            ->willReturn($cacheItem)
        ;

        $command = $this->createMock(ConsumeMessagesCommand::class);
        $command
            ->expects($this->once())
            ->method('run')
            ->with(
                $this->callback(static fn (ArrayInput $input): bool => '2' === (string) $input->getParameterOption('--limit')),
                $this->isInstanceOf(NullOutput::class),
            )
            ->willReturn(0)
        ;

        $webWorker = new WebWorker(
            $cache,
            $command,
            $this->mockScopeMatcher(),
            ['transport-1'],
        );

        $sender = $this->createMock(SenderInterface::class);
        $webWorker->onMessageDispatched(new SendMessageToTransportsEvent(new Envelope(new \stdClass()), ['transport-1' => $sender]));
        $webWorker->onMessageDispatched(new SendMessageToTransportsEvent(new Envelope(new \stdClass()), ['transport-1' => $sender]));

        $webWorker->onKernelTerminate(new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            new Response(),
        ));
    }

    private function triggerWebWorker(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', 'frontend');

        $this->eventDispatcher->dispatch(new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            new Response(),
        ));
    }

    private function triggerRealWorkers(array $transports): void
    {
        $listener = static function (WorkerRunningEvent $event): void {
            if ($event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }
        };

        $this->eventDispatcher->addListener(WorkerRunningEvent::class, $listener);

        $input = new ArrayInput([
            'receivers' => $transports,
        ]);

        $this->command->run($input, new NullOutput());
        $this->eventDispatcher->removeListener(WorkerRunningEvent::class, $listener);
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
            SendMessageToTransportsEvent::class,
            static function (SendMessageToTransportsEvent $event) use ($webWorker): void {
                $webWorker->onMessageDispatched($event);
            },
        );

        $this->eventDispatcher->addListener(
            TerminateEvent::class,
            static function (TerminateEvent $event) use ($webWorker): void {
                $webWorker->onKernelTerminate($event);
            },
        );
    }
}
