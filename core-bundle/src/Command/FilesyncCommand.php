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

use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Synchronizes the file system with the database.
 */
class FilesyncCommand extends Command
{
    /** @var Dbafs */
    private $dbafs;

    public function __construct(Dbafs $dbafs)
    {
        parent::__construct();

        $this->dbafs = $dbafs;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:filesync')
            ->setDescription('Synchronizes the file system with the database.')
            ->addOption('dry', 'd', InputOption::VALUE_NONE, 'Run dry, do not apply changes.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = $input->getOption('dry');

        $output->writeln('Synchronizing...'.($dryRun ? ' [dry run]' : ''));

        $time = microtime(true);
        $changeSet = $this->dbafs->sync($dryRun);
        $timeTotal = round(microtime(true) - $time, 2);

        $changeSet->renderStats($output);
        $io = new SymfonyStyle($input, $output);
        $io->success("Synchronization complete in {$timeTotal}s.");

        return 0;
    }
}
