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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[AsCommand(
    name: 'contao:install',
    description: 'Installs the required Contao directories.'
)]
class InstallCommand extends Command
{
    private Filesystem|null $fs = null;
    private array $rows = [];
    private string|null $webDir = null;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $uploadPath,
        private readonly string $imageDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('target', InputArgument::OPTIONAL, 'The target directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->webDir = $input->getArgument('target') ?? 'public';

        $this->addEmptyDirs();

        if (!empty($this->rows)) {
            $io = new SymfonyStyle($input, $output);
            $io->newLine();
            $io->listing($this->rows);
        }

        return Command::SUCCESS;
    }

    private function addEmptyDirs(): void
    {
        static $emptyDirs = [
            'assets/css',
            'assets/js',
            'system',
            'system/cache',
            'system/config',
            'system/modules',
            'system/themes',
            'system/tmp',
            'templates',
            '%s/share',
            '%s/system',
        ];

        foreach ($emptyDirs as $path) {
            $this->addEmptyDir(Path::join($this->projectDir, sprintf($path, $this->webDir)));
        }

        $this->addEmptyDir($this->imageDir);
        $this->addEmptyDir(Path::join($this->projectDir, $this->uploadPath));
    }

    private function addEmptyDir(string $path): void
    {
        if ($this->fs->exists($path)) {
            return;
        }

        $this->fs->mkdir($path);

        $this->rows[] = Path::makeRelative($path, $this->projectDir);
    }
}
