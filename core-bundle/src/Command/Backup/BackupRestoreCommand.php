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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:backup:restore',
    description: 'Restores a database backup.',
)]
class BackupRestoreCommand extends AbstractBackupCommand
{
    #[\Override]
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', null, InputOption::VALUE_NONE, 'By default, this command only restores backup that have been generated with Contao. Use --force to bypass this check.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->backupManager->createRestoreConfig();
        $config = $this->handleCommonConfig($input, $config);

        if ($input->getOption('force')) {
            $config = $config->withIgnoreOriginCheck(true);
        }

        try {
            $this->backupManager->restore($config);
        } catch (BackupManagerException $e) {
            if ($this->isJson($input)) {
                $io->writeln(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));
            } else {
                $io->error($e->getMessage());
            }

            return Command::FAILURE;
        }

        if ($this->isJson($input)) {
            $io->writeln(json_encode($config->getBackup()->toArray(), JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        $io->success(sprintf('Successfully restored backup from "%s".', $config->getBackup()->getFilename()));

        return Command::SUCCESS;
    }
}
