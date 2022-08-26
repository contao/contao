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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class BackupCreateCommand extends AbstractBackupCommand
{
    protected static $defaultName = 'contao:backup:create';
    protected static $defaultDescription = 'Creates a new database backup.';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->backupManager->createCreateConfig();
        $config = $this->handleCommonConfig($input, $config);

        try {
            $this->backupManager->create($config);
        } catch (BackupManagerException $e) {
            if ($this->isJson($input)) {
                $io->writeln(json_encode(['error' => $e->getMessage()]));
            } else {
                $io->error($e->getMessage());
            }

            return 1;
        }

        if ($this->isJson($input)) {
            $io->writeln(json_encode($config->getBackup()->toArray()));

            return 0;
        }

        $io->success(sprintf('Successfully created SQL dump "%s".', $config->getBackup()->getFilename()));

        return 0;
    }
}
