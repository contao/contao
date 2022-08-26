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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command
{
    protected static $defaultName = 'contao:cron';
    protected static $defaultDescription = 'Runs all registered cron jobs on the command line.';

    protected Cron $cron;

    public function __construct(Cron $cron)
    {
        $this->cron = $cron;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->cron->run(Cron::SCOPE_CLI);

        return 0;
    }
}
