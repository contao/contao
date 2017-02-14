<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
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
     *
     * @var array
     */
    private $optionalFiles = [
        '.htaccess',
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
        $webDir = rtrim($baseDir, '/').'/web';

        $this->addFiles($webDir);

        return 0;
    }

    /**
     * Adds files from Resources/web to the application's web directory.
     *
     * @param string $webDir
     */
    private function addFiles($webDir)
    {
        $finder = Finder::create()->files()->ignoreDotFiles(false)->in(__DIR__.'/../Resources/web');

        foreach ($finder as $file) {
            if (in_array($file->getRelativePathname(), $this->optionalFiles, true)
                && $this->fs->exists($webDir.'/'.$file->getRelativePathname())
            ) {
                continue;
            }

            $this->fs->copy($file->getPathname(), $webDir.'/'.$file->getRelativePathname(), true);

            $this->io->text(sprintf('Added the <comment>%s</comment> file.', $file->getFilename()));
        }
    }
}
