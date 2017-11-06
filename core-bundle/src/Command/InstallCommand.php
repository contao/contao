<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Installs the required Contao directories.
 */
class InstallCommand extends AbstractLockedCommand
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
    private $webDir;

    /**
     * @var array
     */
    private static $emptyDirs = [
        'system',
        'system/config',
        'templates',
        '%s/system',
    ];

    /**
     * @var array
     */
    private static $ignoredDirs = [
        'assets/css',
        'assets/js',
        'system/cache',
        'system/modules',
        'system/themes',
        'system/tmp',
        '%s/share',
    ];

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
    protected function executeLocked(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);
        $this->rootDir = $this->getContainer()->getParameter('kernel.project_dir');
        $this->webDir = rtrim($input->getArgument('target'), '/');

        $this->addEmptyDirs();
        $this->addIgnoredDirs();

        if (!empty($this->rows)) {
            $this->io->newLine();
            $this->io->listing($this->rows);
        }

        return 0;
    }

    /**
     * Adds the empty directories.
     */
    private function addEmptyDirs(): void
    {
        foreach (self::$emptyDirs as $path) {
            $this->addEmptyDir($this->rootDir.'/'.sprintf($path, $this->webDir));
        }

        $this->addEmptyDir($this->rootDir.'/'.$this->getContainer()->getParameter('contao.upload_path'));
    }

    /**
     * Adds an empty directory.
     *
     * @param string $path
     */
    private function addEmptyDir(string $path): void
    {
        if ($this->fs->exists($path)) {
            return;
        }

        $this->fs->mkdir($path);

        $this->rows[] = str_replace($this->rootDir.'/', '', $path);
    }

    /**
     * Adds the ignored directories.
     */
    private function addIgnoredDirs(): void
    {
        foreach (self::$ignoredDirs as $path) {
            $this->addIgnoredDir($this->rootDir.'/'.sprintf($path, $this->webDir));
        }

        $this->addIgnoredDir($this->getContainer()->getParameter('contao.image.target_dir'));
    }

    /**
     * Adds a directory with a .gitignore file.
     *
     * @param string $path
     */
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
