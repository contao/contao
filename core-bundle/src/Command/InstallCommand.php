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
use Webmozart\PathUtil\Path;

/**
 * Installs the required Contao directories.
 *
 * @internal
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
     * @var string
     */
    private $webDir;

    public function __construct(string $rootDir, string $uploadPath, string $imageDir)
    {
        $this->rootDir = $rootDir;
        $this->uploadPath = $uploadPath;
        $this->imageDir = $imageDir;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('contao:install')
            ->addArgument('target', InputArgument::OPTIONAL, 'The target directory', 'web')
            ->setDescription('Installs the required Contao directories')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);
        $this->webDir = $input->getArgument('target');

        $this->addEmptyDirs();

        if (!empty($this->rows)) {
            $this->io->newLine();
            $this->io->listing($this->rows);
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
            $this->addEmptyDir(Path::join($this->rootDir, sprintf($path, $this->webDir)));
        }

        $this->addEmptyDir($this->imageDir);
        $this->addEmptyDir(Path::join($this->rootDir, $this->uploadPath));
    }

    private function addEmptyDir(string $path): void
    {
        if ($this->fs->exists($path)) {
            return;
        }

        $this->fs->mkdir($path);

        $this->rows[] = Path::makeRelative($path, $this->rootDir);
    }
}
