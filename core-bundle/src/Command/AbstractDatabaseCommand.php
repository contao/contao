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

use Contao\CoreBundle\Doctrine\Dumper\Config\AbstractConfig;
use Contao\CoreBundle\Doctrine\Dumper\Dumper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class AbstractDatabaseCommand extends Command
{
    protected Dumper $databaseDumper;

    public function __construct(Dumper $databaseDumper)
    {
        $this->databaseDumper = $databaseDumper;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'The path to the SQL dump.')
            ->addOption('ignore-tables', 'i', InputOption::VALUE_OPTIONAL, 'A comma-separated list of database tables to ignore. Defaults to the Contao configuration (contao.db.dump.ignoreTables).')
        ;
    }

    protected function handleCommonConfig(InputInterface $input, AbstractConfig $config): AbstractConfig
    {
        if ($file = $input->getArgument('file')) {
            $config = $config->withFilePath($file);
        }

        if ($tablesToIgnore = $input->getOption('ignore-tables')) {
            $config = $config->withTablesToIgnore(explode(',', $tablesToIgnore));
        }

        return $config;
    }
}
