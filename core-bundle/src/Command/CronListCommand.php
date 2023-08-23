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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:cron:list',
    description: 'Lists all available cron jobs and their intervals.',
)]
class CronListCommand extends Command
{
    public function __construct(private readonly Cron $cron)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, json)', 'txt');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $format = $input->getOption('format');

        if (!\in_array($format, ['json', 'txt'], true)) {
            throw new \InvalidArgumentException('This command only supports the "txt" and "json" formats.');
        }

        $io = new SymfonyStyle($input, $output);
        $cronJobs = $this->cron->getCronJobs();
        $list = [];

        if ('json' === $format) {
            foreach ($cronJobs as $cronJob) {
                $list[] = [
                    'name' => $cronJob->getName(),
                    'interval' => $cronJob->getInterval(),
                ];
            }

            $io->writeln(json_encode($list));

            return 0;
        }

        foreach ($cronJobs as $cronJob) {
            $list[] = [$cronJob->getName(), $cronJob->getInterval()];
        }

        $table = new Table($output);
        $table->setHeaders(['Name', 'Interval'])->setRows($list);
        $table->render();

        return Command::SUCCESS;
    }
}
