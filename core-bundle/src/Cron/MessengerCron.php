<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Util\ProcessUtil;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;

#[AsCronJob('minutely')]
class MessengerCron
{
    /**
     * @param array<array{'options': array<string>, 'transports': array<string>, 'autoscale': array{'enabled': bool, 'desired_size': int, 'max': int}}> $workers
     */
    public function __construct(private ContainerInterface $messengerTransportLocator, private ProcessUtil $processUtil, private string $consolePath, private array $workers)
    {
    }

    public function __invoke(string $scope): PromiseInterface|null
    {
        if (Cron::SCOPE_CLI !== $scope) {
            return null;
        }

        $workerPromises = [];

        foreach ($this->workers as $worker) {
            $this->addWorkerPromises($worker, $workerPromises);
        }

        return Utils::all($workerPromises);
    }

    /**
     * @param array{'options': array<string>, 'transports': array<string>, 'autoscale': array{'enabled': bool, 'desired_size': int, 'max': int}} $worker
     */
    private function addWorkerPromises(array $worker, array &$workerPromises): void
    {
        // Always add one worker
        $workerPromises[] = $this->createProcessPromiseForWorker($worker);

        if ($worker['autoscale']['enabled']) {
            $totalMessages = $this->collectTotalMessages($worker['transports']);
            $desiredWorkers = ceil($totalMessages / $worker['autoscale']['desired_size']);

            // Never more than the max
            $desiredWorkers = min($desiredWorkers, $worker['autoscale']['max']);

            // Subtract by one because we already started one and make sure $desiredWorkers
            // is never negative (possible if totalMessages is 0)
            $desiredWorkers = max(0, $desiredWorkers - 1);

            for ($i = 1; $i <= $desiredWorkers; ++$i) {
                $workerPromises[] = $this->createProcessPromiseForWorker($worker);
            }
        }
    }

    /**
     * @param array{'options': array<string>, 'transports': array<string>, 'autoscale': array{'enabled': bool, 'desired_size': int, 'max': int}} $worker
     */
    private function createProcessPromiseForWorker(array $worker): PromiseInterface
    {
        $process = $this->processUtil->createSymfonyConsoleProcess(
            $this->consolePath,
            'messenger:consume',
            ...array_merge($worker['options'], $worker['transports'])
        );

        return $this->processUtil->createPromise($process);
    }

    private function collectTotalMessages(array $transportNames): int
    {
        $total = 0;

        foreach ($transportNames as $transportName) {
            if (!$this->messengerTransportLocator->has($transportName)) {
                throw new \LogicException(sprintf('Configuration error! There is no transport named "%s" to start a worker for.', $transportName));
            }

            $transport = $this->messengerTransportLocator->get($transportName);

            if (!$transport instanceof MessageCountAwareInterface) {
                throw new \LogicException(sprintf('Configuration error! Cannot enable autoscaling for transport "%s".', $transportName));
            }

            $total += $transport->getMessageCount();
        }

        return $total;
    }
}
