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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:backup:create',
    description: 'Creates a new database backup.'
)]
class BackupCreateCommand extends AbstractBackupCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->backupManager->createCreateConfig();
        $config = $this->handleCommonConfig($input, $config);

        try {
            $this->backupManager->create($config);
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

        $io->success(sprintf('Successfully created SQL dump "%s".', $config->getBackup()->getFilename()));

        return Command::SUCCESS;
    }
}
