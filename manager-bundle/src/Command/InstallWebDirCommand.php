<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Command;

use Contao\CoreBundle\Command\AbstractLockedCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Installs the web entry points for Contao Managed Edition.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class InstallWebDirCommand extends AbstractLockedCommand
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
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:install-web-dir')
            ->setDescription('Generates entry points in /web directory.')
            ->addArgument('root-dir', InputArgument::OPTIONAL, 'The installation root directory (defaults to the current working directory).', getcwd())
            ->addOption('web-dir', '', InputOption::VALUE_REQUIRED, 'Relative path to web directory (defaults to "web")', 'web')
            ->addOption('var-dir', '', InputOption::VALUE_REQUIRED, 'Relative path to var directory (defaults to "var")', 'var')
            ->addOption('vendor-dir', '', InputOption::VALUE_REQUIRED, 'Relative path to the Composer vendor directory (defaults to "vendor")', 'vendor')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite files if they exist.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $rootDir = $input->getArgument('root-dir');
        $webDir = $this->absolutePath($rootDir, $input->getOption('web-dir'));
        $varDir = $this->absolutePath($rootDir, $input->getOption('var-dir'));
        $vendorDir = $this->absolutePath($rootDir, $input->getOption('vendor-dir'));
        $force = (bool) $input->getOption('force');

        $pathToSystem = rtrim($this->fs->makePathRelative($varDir, $webDir), '/');
        $pathToVendor = rtrim($this->fs->makePathRelative($vendorDir, $webDir), '/');

        $this->addFiles($webDir, $pathToSystem, $pathToVendor, $force);

        return 0;
    }

    /**
     * Create an absolute path from root and relative directory.
     *
     * @param string $rootDir
     * @param string $path
     *
     * @return string
     */
    private function absolutePath($rootDir, $path)
    {
        return realpath(rtrim($rootDir, '/') . '/' . trim($path, '/'));
    }

    /**
     * Adds files from Resources/web to the application's web directory.
     *
     * @param string $webDir
     * @param string $pathToSystem
     * @param string $pathToVendor
     * @param bool   $force
     */
    private function addFiles($webDir, $pathToSystem, $pathToVendor, $force = false)
    {
        $finder = Finder::create()->files()->in(__DIR__ . '/../Resources/web');

        foreach ($finder as $file) {
            if ($this->fs->exists($webDir.'/'.$file->getRelativePathname()) && !$force) {
                continue;
            }

            $content = str_replace(
                ['{system-dir}', '{vendor-dir}'],
                [$pathToSystem, $pathToVendor],
                file_get_contents($file->getPathname())
            );

            $this->fs->dumpFile(
                $webDir.'/'.$file->getRelativePathname(),
                $content
            );

            $this->io->text(sprintf('Added the <comment>%s</comment> file.', $file->getFilename()));
        }
    }
}
