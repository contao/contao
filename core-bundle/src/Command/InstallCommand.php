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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

/**
 * Installs the required Contao directories.
 *
 * @internal
 */
class InstallCommand extends Command
{
    protected static $defaultName = 'contao:install';
    protected static $defaultDescription = 'Installs the required Contao directories.';

    private ?Filesystem $fs = null;
    private array $rows = [];
    private string $projectDir;
    private string $uploadPath;
    private string $imageDir;
    private ?string $webDir;

    public function __construct(string $projectDir, string $uploadPath, string $imageDir)
    {
        $this->projectDir = $projectDir;
        $this->uploadPath = $uploadPath;
        $this->imageDir = $imageDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('target', InputArgument::OPTIONAL, 'The target directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->webDir = $input->getArgument('target');

        if (null === $this->webDir) {
            if ($this->fs->exists($this->projectDir.'/web')) {
                $this->webDir = 'web'; // backwards compatibility
            } else {
                $this->webDir = 'public';
            }
        }

        $this->addEmptyDirs();

        if (!empty($this->rows)) {
            $io = new SymfonyStyle($input, $output);
            $io->newLine();
            $io->listing($this->rows);
        }

        return 0;
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
