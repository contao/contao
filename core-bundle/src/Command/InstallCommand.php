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
use Symfony\Component\Lock\LockInterface;

/**
 * Installs the required Contao directories.
 */
class InstallCommand extends Command
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var SymfonyStyle
     */
    private $io;

    /**
     * @var array
     */
    private $rows = [];

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $uploadPath;

    /**
     * @var string
     */
    private $imageDir;

    /**
     * @var LockInterface
     */
    private $lock;

    /**
     * @var string
     */
    private $webDir;

    public function __construct(string $rootDir, string $uploadPath, string $imageDir, LockInterface $lock)
    {
        $this->rootDir = $rootDir;
        $this->uploadPath = $uploadPath;
        $this->imageDir = $imageDir;
        $this->lock = $lock;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('contao:install')
            ->setDefinition([
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web'),
            ])
            ->setDescription('Installs the required Contao directories')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock->acquire()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);
        $this->webDir = rtrim($input->getArgument('target'), '/');

        $this->addEmptyDirs();
        $this->addIgnoredDirs();

        if (!empty($this->rows)) {
            $this->io->newLine();
            $this->io->listing($this->rows);
        }

        $this->lock->release();

        return 0;
    }

    private function addEmptyDirs(): void
    {
        static $emptyDirs = [
            'system',
            'system/config',
            'templates',
            '%s/system',
        ];

        foreach ($emptyDirs as $path) {
            $this->addEmptyDir($this->rootDir.'/'.sprintf($path, $this->webDir));
        }

        $this->addEmptyDir($this->rootDir.'/'.$this->uploadPath);
    }

    private function addEmptyDir(string $path): void
    {
        if ($this->fs->exists($path)) {
            return;
        }

        $this->fs->mkdir($path);

        $this->rows[] = str_replace($this->rootDir.'/', '', $path);
    }

    private function addIgnoredDirs(): void
    {
        static $ignoredDirs = [
            'assets/css',
            'assets/js',
            'system/cache',
            'system/modules',
            'system/themes',
            'system/tmp',
            '%s/share',
        ];

        foreach ($ignoredDirs as $path) {
            $this->addIgnoredDir($this->rootDir.'/'.sprintf($path, $this->webDir));
        }

        $this->addIgnoredDir($this->imageDir);
    }

    private function addIgnoredDir(string $path): void
    {
        $this->addEmptyDir($path);

        if ($this->fs->exists($path.'/.gitignore')) {
            return;
        }

        $this->fs->dumpFile(
            $path.'/.gitignore',
            "# Create the folder and ignore its content\n*\n!.gitignore\n"
        );
    }
}
