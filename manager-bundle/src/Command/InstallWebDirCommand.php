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
     * Files that should not be copied if they exist in the web directory.
     * @var array
     */
    private $optionalFiles = [
        '.htaccess'
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:install-web-dir')
            ->setDescription('Generates entry points in /web directory.')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The installation root directory (defaults to the current working directory).',
                getcwd()
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $this->io = new SymfonyStyle($input, $output);

        $baseDir = $input->getArgument('path');
        $webDir = $this->absolutePath($baseDir, 'web');
        $rootDir = $this->absolutePath($baseDir, 'app');
        $vendorDir = $this->absolutePath($baseDir, 'vendor');

        $this->addFiles(
            $webDir,
            rtrim($this->fs->makePathRelative($rootDir, $webDir), '/'),
            rtrim($this->fs->makePathRelative($vendorDir, $webDir), '/')
        );

        return 0;
    }

    /**
     * Create an absolute path from root and relative directory.
     *
     * @param string $baseDir
     * @param string $path
     *
     * @return string
     */
    private function absolutePath($baseDir, $path)
    {
        return rtrim($baseDir, '/').'/'.trim($path, '/');
    }

    /**
     * Adds files from Resources/web to the application's web directory.
     *
     * @param string $webDir
     * @param string $rootDir
     * @param string $vendorDir
     */
    private function addFiles($webDir, $rootDir, $vendorDir)
    {
        $finder = Finder::create()->files()->ignoreDotFiles(false)->in(__DIR__.'/../Resources/web');

        foreach ($finder as $file) {
            if (in_array($file->getRelativePathname(), $this->optionalFiles, true)
                && $this->fs->exists($webDir . '/' . $file->getRelativePathname())
            ) {
                continue;
            }

            $content = str_replace(
                ['{root-dir}', '{vendor-dir}'],
                [$rootDir, $vendorDir],
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
