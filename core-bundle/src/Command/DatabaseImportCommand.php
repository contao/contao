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

use Contao\CoreBundle\Doctrine\Dumper\DumperException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Imports.
 *
 * @internal
 */
class DatabaseImportCommand extends AbstractDatabaseCommand
{
    protected static $defaultName = 'contao:database:import';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('truncate', 't', InputOption::VALUE_NONE, 'Truncate the existing database')
            ->setDescription('Imports an SQL dump directly to the database.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->databaseDumper->createDefaultImportConfig();
        $config = $this->handleCommonConfig($input, $config);

        if ($input->getOption('truncate')) {
            $config = $config->withMustTruncate(true);
        }

        try {
            $this->databaseDumper->import($config);
        } catch (DumperException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success('Successfully imported SQL dump.');

        return 0;
    }
}
