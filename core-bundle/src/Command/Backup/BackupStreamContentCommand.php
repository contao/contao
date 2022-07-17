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

use Contao\CoreBundle\Doctrine\Backup\BackupManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'contao:backup:stream-content')]
class BackupStreamContentCommand extends Command
{
    public function __construct(protected BackupManager $backupManager)
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$output instanceof StreamOutput) {
            $io->error('OutputInterface must be instance of StreamOutput for this command to work.');

            return Command::FAILURE;
        }

        if (null === ($backup = $this->backupManager->getBackupByName($input->getArgument('name')))) {
            $io->error(sprintf('Backup "%s" not found.', $input->getArgument('name')));

            return Command::FAILURE;
        }

        stream_copy_to_stream($this->backupManager->readStream($backup), $output->getStream());

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the backup')
            ->setHidden(true)
        ;
    }
}
