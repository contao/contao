<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'contao:install-bin-dir',
    description: 'Installs the bin/console file.'
)]
class InstallBinDirCommand extends Command
{
    public function __construct(private string $projectDir)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $io = new SymfonyStyle($input, $output);

        $sourcePath = Path::canonicalize(__DIR__.'/../../skeleton/bin/console');
        $targetPath = Path::join($this->projectDir, 'bin/console');

        $fs->copy($sourcePath, $targetPath, true);
        $io->writeln("Added the <comment>$targetPath</comment> file.");

        return Command::SUCCESS;
    }
}
