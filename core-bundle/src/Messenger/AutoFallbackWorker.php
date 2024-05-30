<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Messenger;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;

/**
 * This service automatically detects which Symfony Messenger transports have real
 * workers running when a message is dispatched to the message bus. In case they
 * do not, it falls back to a kernel.terminate worker to ensure, messages are
 * always processed, no matter whether a real worker is running or not.
 *
 * Detecting works by using the WorkerStartedEvent and WorkerRunningEvent which
 * sets/updates a cache items and allowing a grace period.
 *
 * In any case, this provides advantages as it allows us to always use a queue and
 * postpone processes to at least kernel.terminate in case there are no real
 * workers available!
 */
class AutoFallbackWorker
{
    private const GRACE_PERIOD = 'PT60S'; // If no real worker "pings" us within this grace period, we run our fallback worker

    private const ADDITIONAL_MESSAGES = 1;

    private array $messagesSentDuringRequest = [];

    private bool $fallbackWorkerRunning = false;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ConsumeMessagesCommand $consumeMessagesCommand,
    ) {
    }

    #[AsEventListener(priority: -512)]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        foreach ($this->messagesSentDuringRequest as $transportName => $messageCount) {
            if ($this->isWorkerRunning($transportName)) {
                continue;
            }

            // Always process a little more than only the accumulated messages during this
            // request. This is to ensure that messages do not get lost. Something that
            // shouldn't happen but could happen if e.g. real workers are getting killed due
            // to server hickups or so.
            $this->processTransport($transportName, $messageCount + self::ADDITIONAL_MESSAGES);
        }

        $this->messagesSentDuringRequest = [];
    }

    #[AsEventListener]
    public function onSendMessageToTransports(SendMessageToTransportsEvent $event): void
    {
        // If we are running in our fallback worker process, we don't count
        if ($this->fallbackWorkerRunning) {
            return;
        }

        foreach (array_keys($event->getSenders()) as $transportName) {
            $this->increaseMessageCountForTransportName($transportName);
        }
    }

    #[AsEventListener]
    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        // If we are running in our fallback worker process, we never ping (otherwise we
        // would self-disable)
        if ($this->fallbackWorkerRunning) {
            return;
        }

        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            $this->ping($transportName);
        }
    }

    #[AsEventListener]
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        // If we are running in our fallback worker process, we never ping (otherwise we
        // would self-disable)
        if ($this->fallbackWorkerRunning) {
            // If we process more messages than accumulated during our request, we start the
            // kernel.terminate worker with a --limit that is potentially higher than what's
            // on the queue in total. This would cause the worker to run endlessly (or until
            // reaching the time limit). This is not necessary. If we know this is our own
            // fallback worker, and it is idle, we can stop it right away to free the process
            // for the next requests.
            //
            // This check is added deliberately to prevent forgetting to remove this in case the ADDITONAL_MESSAGES
            // feature ever gets removed, so ignore for phpstan
            // @phpstan-ignore-next-line
            if (self::ADDITIONAL_MESSAGES > 0 && $event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }

            return;
        }

        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            $this->ping($transportName);
        }
    }

    #[AsEventListener]
    public function onWorkerStopped(WorkerStoppedEvent $event): void
    {
        $this->fallbackWorkerRunning = false;
    }

    public function ping(string $transportName): void
    {
        $item = $this->getCacheItemForTransportName($transportName);
        $item->expiresAfter(new \DateInterval(self::GRACE_PERIOD));

        $this->cache->save($item);
    }

    private function isWorkerRunning(string $transportName): bool
    {
        return $this->getCacheItemForTransportName($transportName)->isHit();
    }

    private function getCacheItemForTransportName(string $transportName): CacheItemInterface
    {
        return $this->cache->getItem('auto-fallback-worker-'.$transportName);
    }

    private function increaseMessageCountForTransportName(string $transportName): void
    {
        if (!isset($this->messagesSentDuringRequest[$transportName])) {
            $this->messagesSentDuringRequest[$transportName] = 0;
        }

        ++$this->messagesSentDuringRequest[$transportName];
    }

    private function processTransport(string $transportName, int $limit): void
    {
        $this->fallbackWorkerRunning = true;
        $input = new ArrayInput([
            'receivers' => [$transportName],
            '--limit' => $limit, // This is the total message number limit
            '--time-limit' => 30, // Ensure the kernel.terminate worker doesn't run forever in case of misconfiguration
        ]);

        // No need to log anything because this is done by the messenger:consume command
        // already and would only cause log duplication.
        $this->consumeMessagesCommand->run($input, new NullOutput());
        $this->fallbackWorkerRunning = false;
    }
}
