<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Util\ProcessUtil;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Toflar\CronjobSupervisor\BasicCommand;
use Toflar\CronjobSupervisor\CommandInterface;
use Toflar\CronjobSupervisor\Supervisor;

#[AsCommand(
    name: 'contao:supervise-workers',
    description: 'Supervises the Contao workers.',
)]
class SuperviseWorkersCommand extends Command
{
    /**
     * @param array<array{'options': array<string>, 'transports': array<string>, 'autoscale': array{'enabled': bool, 'desired_size': int, 'max': int, 'min': int}}> $workers
     */
    public function __construct(
        private readonly ContainerInterface $messengerTransportLocator,
        private readonly ProcessUtil $processUtil,
        private Supervisor $supervisor,
        private readonly array $workers,
    ) {
        parent::__construct();
    }

    public static function create(ContainerInterface $messengerTransportLocator, ProcessUtil $processUtil, string $storageDirectory, array $workers): self
    {
        return new self(
            $messengerTransportLocator,
            $processUtil,
            new Supervisor($storageDirectory),
            $workers,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach ($this->workers as $k => $worker) {
            $this->supervisor = $this->supervisor->withCommand($this->createCommandForWorker('worker-'.$k + 1, $worker));
        }

        $io->info('Starting to supervise workers for a minute.');
        $this->supervisor->supervise();
        $io->info('Done. Restart this command to spin up the workers and have them being supervised them again.');

        return Command::SUCCESS;
    }

    /**
     * @param array{'options': array<string>, 'transports': array<string>, 'autoscale': array{'enabled': bool, 'desired_size': int, 'max': int, 'min': int}} $worker
     */
    private function createCommandForWorker(string $identifier, array $worker): CommandInterface
    {
        // Always start one worker
        $desiredWorkers = 1;

        if ($worker['autoscale']['enabled']) {
            $totalMessages = $this->collectTotalMessages($worker['transports']);
            $desiredWorkers = (int) round(ceil($totalMessages / $worker['autoscale']['desired_size']));

            // Never more than the max
            $desiredWorkers = (int) min($desiredWorkers, $worker['autoscale']['max']);

            // Never less than the min
            $desiredWorkers = (int) max($worker['autoscale']['min'], $desiredWorkers);
        }

        return new BasicCommand(
            $identifier,
            $desiredWorkers,
            function () use ($worker) {
                return $this->processUtil->createSymfonyConsoleProcess(
                    'messenger:consume',
                    ...$worker['options'],
                    ...$worker['transports'],
                );
            },
        );
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
