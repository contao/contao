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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;
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
    private const MAX_DURATION = 30;

    private bool $webWorkerRunning = false;

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ConsumeMessagesCommand $consumeMessagesCommand,
        private readonly array $transports,
        private readonly string $gracePeriod = 'PT10M',
    ) {
    }

    /**
     * The priority must be lower than the one of the profiler listener, so resetting
     * the services will not affect collecting the profiler information.
     */
    #[AsEventListener(priority: -2048)]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $stopTime = $this->calculateStopTime($event->getRequest());

        foreach ($this->transports as $transportName) {
            $this->processTransport($transportName, $stopTime);
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

    private function processTransport(string $transportName, float $stopTime): void
    {
        // Real worker is running, abort
        if ($this->getCacheItemForTransportName($transportName)->isHit()) {
            return;
        }

        $timeLimit = round($stopTime - microtime(true));

        if ($timeLimit < 1) {
            return;
        }

        $this->webWorkerRunning = true;

        $inputParameters = [
            'receivers' => [$transportName],
            '--time-limit' => $timeLimit,
            '--sleep' => 0,
        ];

        // This ensures that we also consider configured memory limits in order to try to
        // not process more messages than the configured memory limit allows. Meaning
        // this will either abort after having consumed the configured memory limit for
        // the web process $timeLimit seconds - whichever limit is hit first.
        if (($memoryLimit = (string) \ini_get('memory_limit')) && '-1' !== $memoryLimit) {
            $inputParameters['--memory-limit'] = $memoryLimit;
        }

        $input = new ArrayInput($inputParameters);

        // No need to log anything because this is done by the messenger:consume command
        // already and would only cause log duplication.
        $this->consumeMessagesCommand->run($input, new NullOutput());

        $this->webWorkerRunning = false;
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

        // Substract already used up execution time
        return $maxTime - (microtime(true) - ($request->server->get('REQUEST_TIME_FLOAT') ?? microtime(true)));
    }
}
