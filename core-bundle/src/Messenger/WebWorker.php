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

use Contao\CoreBundle\Messenger\Message\ScopeAwareMessageInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

/**
 * This service accepts an array of Symfony Messenger transports and automatically
 * detects which ones have real workers running. In case they have not, it falls
 * back to a web worker at "kernel.terminate" to ensure that messages are always
 * processed, no matter whether a real worker is running or not.
 *
 * Detecting works by using the WorkerStartedEvent and WorkerRunningEvent which
 * sets/updates a cache item and allows a grace period. The grace period defines
 * how long after the last event the web worker should consider a real worker to
 * be running. Imagine you only have one worker running, and it works on a message
 * for 10 minutes. In such a case, no WorkerStartedEvent or WorkerRunningEvent
 * will be triggered for 10 minutes, so if there were a grace period of only a few
 * seconds, the web worker would probably jump in too quickly.
 *
 * In any case, this allows us to always use a queue and postpone processes to at
 * least the "kernel.terminate" event if there are no real workers available!
 */
class WebWorker
{
    final public const REQUEST_ATTRIBUTE_ENABLE = '_contao_web_worker';

    private const MAX_DURATION = 30;

    private bool $webWorkerRunning = false;

    /**
     * @var array<string, int>
     */
    private array $dispatchedMessagesByTransport = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ConsumeMessagesCommand $consumeMessagesCommand,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly array $transports,
        private readonly string $gracePeriod = 'PT10M',
    ) {
    }

    public function hasCliWorkersRunning(): bool
    {
        foreach ($this->transports as $transportName) {
            if ($this->getCacheItemForTransportName($transportName)->isHit()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The priority must be lower than the one of the profiler listener, so resetting
     * the services will not affect collecting the profiler information.
     */
    #[AsEventListener(priority: -2048)]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($this->allowsQueueDraining($event)) {
            $stopTime = $this->calculateStopTime($event->getRequest());

            foreach ($this->transports as $transportName) {
                $this->processTransportByStopTime($transportName, $stopTime);
            }
        } else {
            foreach ($this->dispatchedMessagesByTransport as $transportName => $count) {
                $this->processTransportByLimit($transportName, $count);
            }
        }

        $this->dispatchedMessagesByTransport = [];
    }

    #[AsEventListener]
    public function onMessageDispatched(SendMessageToTransportsEvent $event): void
    {
        foreach (array_keys($event->getSenders()) as $transportName) {
            if (!\in_array($transportName, $this->transports, true)) {
                continue;
            }

            $this->dispatchedMessagesByTransport[$transportName] = ($this->dispatchedMessagesByTransport[$transportName] ?? 0) + 1;
        }
    }

    #[AsEventListener]
    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            $this->ping($transportName);
        }
    }

    #[AsEventListener]
    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        // Stop idle web workers to free the web process.
        if ($this->webWorkerRunning && $event->isWorkerIdle()) {
            $event->getWorker()->stop();

            return;
        }

        foreach ($event->getWorker()->getMetadata()->getTransportNames() as $transportName) {
            $this->ping($transportName);
        }
    }

    #[AsEventListener]
    public function onWorkerMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if ($message instanceof ScopeAwareMessageInterface) {
            $message->setScope($this->webWorkerRunning ? ScopeAwareMessageInterface::SCOPE_WEB : ScopeAwareMessageInterface::SCOPE_CLI);
        }
    }

    public function ping(string $transportName): void
    {
        // If we are running in our web worker process, we never ping (otherwise we
        // would self-disable)
        if ($this->webWorkerRunning) {
            return;
        }

        // If this is a transport we don't care about, we don't do anything either
        if (!\in_array($transportName, $this->transports, true)) {
            return;
        }

        $item = $this->getCacheItemForTransportName($transportName);
        $item->expiresAfter(new \DateInterval($this->gracePeriod));

        $this->cache->save($item);
    }

    private function getCacheItemForTransportName(string $transportName): CacheItemInterface
    {
        return $this->cache->getItem('contao-web-worker-'.$transportName);
    }

    private function processTransportByStopTime(string $transportName, float $stopTime): void
    {
        $timeLimit = round($stopTime - microtime(true));

        if ($timeLimit < 1) {
            return;
        }

        $this->runTransport($transportName, ['--time-limit' => $timeLimit]);
    }

    private function processTransportByLimit(string $transportName, int $limit): void
    {
        if ($limit < 1) {
            return;
        }

        $this->runTransport($transportName, ['--limit' => $limit]);
    }

    private function runTransport(string $transportName, array $options): void
    {
        // Real worker is running, abort
        if ($this->getCacheItemForTransportName($transportName)->isHit()) {
            return;
        }

        $this->webWorkerRunning = true;

        $inputParameters = [
            'receivers' => [$transportName],
            '--sleep' => 0,
            ...$options,
        ];

        // This ensures that we also consider configured memory limits in order to try
        // not processing more messages than the configured memory limit allows.
        if (($memoryLimit = (string) \ini_get('memory_limit')) && '-1' !== $memoryLimit) {
            $inputParameters['--memory-limit'] = $memoryLimit;
        }

        $input = new ArrayInput($inputParameters);

        // No need to log anything because this is done by the messenger:consume command
        // already and would only cause log duplication.
        $this->consumeMessagesCommand->run($input, new NullOutput());

        $this->webWorkerRunning = false;
    }

    /**
     * Queue draining is only allowed for Contao main requests or when explicitly enabled.
     * Otherwise, processing is restricted to messages dispatched in this request.
     */
    private function allowsQueueDraining(TerminateEvent $event): bool
    {
        $request = $event->getRequest();

        // The feature is enabled explicitly
        if (true === $request->attributes->get(self::REQUEST_ATTRIBUTE_ENABLE)) {
            return true;
        }

        // Automatically enable the feature for Contao requests unless it is
        // disabled explicitly
        return $this->scopeMatcher->isContaoMainRequest($event)
            && false !== $request->attributes->get(self::REQUEST_ATTRIBUTE_ENABLE);
    }

    private function calculateStopTime(Request $request): float
    {
        // Short time limit for SAPIs that do not support sending the response before
        // finishing the process (e.g. mod_php)
        $timeLimit = 1;

        // For SAPIs that support sending the response before finishing the process, we
        // can run our web worker longer
        if (\function_exists('fastcgi_finish_request') || \function_exists('litespeed_finish_request')) {
            // Subtract 10 seconds to reduce the risk of exceeding the max execution time. If
            // you found this comment because you ran into a timeout, it is likely that some
            // of your messages take many seconds to finish. This would be an indicator that
            // you need to set up real workers to work on your queue. In case you are using
            // the Contao Managed Edition, this is as easy as configuring a minutely cronjob
            // (https://docs.contao.org/manual/en/performance/cronjobs/). Otherwise, refer to
            // the Symfony documentation on Messenger workers.
            $timeLimit = min(self::MAX_DURATION, max(1, $this->getRemainingExecutionTime($request) - 10));
        }

        return microtime(true) + $timeLimit;
    }

    private function getRemainingExecutionTime(Request $request): float|int
    {
        $maxTime = (int) \ini_get('max_execution_time');

        if (1 > $maxTime) {
            return PHP_INT_MAX;
        }

        // Subtract already used up execution time
        return $maxTime - (microtime(true) - ($request->server->get('REQUEST_TIME_FLOAT') ?? microtime(true)));
    }
}
