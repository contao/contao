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

use Contao\CoreBundle\Cron\Cron;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'contao:cron',
    description: 'Runs cron jobs on the command line.'
)]
class CronCommand extends Command
{
    public function __construct(private readonly Cron $cron)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('cronjob', InputArgument::OPTIONAL, 'An optional single cron job to run')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force jobs to run, disregarding their last execution time')
            ->setHelp('Runs all registered cron jobs by default, otherwise a specific cron job.')
            ->addUsage('"Contao\CoreBundle\Cron\PurgeExpiredDataCron::onHourly"')
            ->addUsage('--force')
            ->addUsage('--force "Contao\CoreBundle\Cron\PurgeExpiredDataCron::onHourly"')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');

        if ($cronJobName = $input->getArgument('cronjob')) {
            $this->cron->runJob($cronJobName, Cron::SCOPE_CLI, $force);
        } else {
            $this->cron->run(Cron::SCOPE_CLI, $force);
        }

        return Command::SUCCESS;
    }
}
