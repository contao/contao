<?php

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Cron\ContaoCron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command
{
    /**
     * @var ContaoCron
     */
    protected $cron;

    public function __construct(ContaoCron $cron)
    {
        $this->cron = $cron;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:cron')
            ->setDescription('Runs all registered cron jobs on the command line.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->cron->run(true);
    }
}
