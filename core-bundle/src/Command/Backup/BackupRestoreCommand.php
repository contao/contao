<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Command\Backup;

use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class BackupRestoreCommand extends AbstractBackupCommand
{
    protected static $defaultName = 'contao:backup:restore';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption('no-delete', null, InputOption::VALUE_NONE, 'Do not delete existing tables')
            ->setDescription('Restores a backup.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->backupManager->createRestoreConfig();
        $config = $this->handleCommonConfig($input, $config);

        if ($input->getOption('no-delete')) {
            $config = $config->withDropTables(false);
        }

        try {
            $this->backupManager->restore($config);
        } catch (BackupManagerException $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->success('Successfully restored backup.');

        return 0;
    }
}
