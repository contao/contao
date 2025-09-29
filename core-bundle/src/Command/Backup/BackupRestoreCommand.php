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
use Contao\CoreBundle\Doctrine\Backup\BackupManagerException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'contao:backup:restore',
    description: 'Restores a database backup.',
)]
class BackupRestoreCommand extends AbstractBackupCommand
{
    private SymfonyStyle $io;

    private string|null $backupName = null;

    protected function configure(): void
    {
        parent::configure();

        $this->addOption('force', null, InputOption::VALUE_NONE, 'By default, this command only restores backup that have been generated with Contao. Use --force to bypass this check.');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if ($this->backupName = $input->getArgument('name') ?? null) {
            return;
        }

        $backups = $this->backupManager->listBackups();

        if ([] !== $backups) {
            $choices = array_map(
                fn(Backup $option) => $option->getFilename(),
                $backups
            );

            $question = new ChoiceQuestion('Select a backup (press <return> to use the latest one)', array_values($choices), 0);
            $option = $this->io->askQuestion($question);

            $this->backupName = $option;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->backupManager->createRestoreConfig();

        if (null !== $this->backupName) {
            $config = $config->withFileName($this->backupName);
        }

        if ($tablesToIgnore = $input->getOption('ignore-tables')) {
            $config = $config->withTablesToIgnore(explode(',', (string) $tablesToIgnore));
        }

        if ($input->getOption('force')) {
            $config = $config->withIgnoreOriginCheck(true);
        }

        try {
            $this->backupManager->restore($config);
        } catch (BackupManagerException $e) {
            if ($this->isJson($input)) {
                $this->io->writeln(json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR));
            } else {
                $this->io->error($e->getMessage());
            }

            return Command::FAILURE;
        }

        if ($this->isJson($input)) {
            $this->io->writeln(json_encode($config->getBackup()->toArray(), JSON_THROW_ON_ERROR));

            return Command::SUCCESS;
        }

        $this->io->success(\sprintf('Successfully restored backup from "%s".', $config->getBackup()->getFilename()));

        return Command::SUCCESS;
    }
}
