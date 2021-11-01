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

use Contao\CoreBundle\Doctrine\Backup\Backup;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @internal
 */
class BackupListCommand extends AbstractBackupCommand
{
    protected static $defaultName = 'contao:backup:list';

    protected function configure(): void
    {
        $this
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, json)', 'txt')
            ->setDescription('Lists all backups.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->isJson($input)) {
            $io->writeln($this->formatForJson($this->backupManager->listBackups()));

            return 0;
        }

        $io->table(['Created', 'Size', 'Path'], $this->formatForTable($this->backupManager->listBackups()));

        return 0;
    }

    /**
     * @param array<Backup> $backups
     */
    private function formatForTable(array $backups): array
    {
        $formatted = [];

        foreach ($backups as $backup) {
            $formatted[] = [
                $backup->getCreatedAt()->format('Y-m-d H:i:s'),
                $backup->getHumanReadableSize(),
                $backup->getFilepath(),
            ];
        }

        return $formatted;
    }

    /**
     * @param array<Backup> $backups
     */
    private function formatForJson(array $backups): string
    {
        $json = [];

        foreach ($backups as $backup) {
            $json[] = $backup->toArray();
        }

        return json_encode($json);
    }
}
