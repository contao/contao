<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Installs the required Contao directories.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallCommand extends AbstractLockedCommand implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var array
     */
    private $emptyDirs = [
        'files',
        'system',
        'templates',
        'web/system',
    ];

    /**
     * @var array
     */
    private $ignoredDirs = [
        'assets/css',
        'assets/images',
        'assets/js',
        'system/cache',
        'system/config',
        'system/modules',
        'system/themes',
        'system/tmp',
        'web/share',
        'web/system/cron',
    ];

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('contao:install')
            ->setDescription('Installs the required Contao directories')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $fs      = new Filesystem();
        $rootDir = dirname($this->container->getParameter('kernel.root_dir'));

        foreach ($this->emptyDirs as $path) {
            $this->addEmptyDir($rootDir . '/' . $path, $fs, $output);
        }

        foreach ($this->ignoredDirs as $path) {
            $this->addIgnoredDir($rootDir . '/' . $path, $fs, $output);
        }

        return 0;
    }

    /**
     * Adds an empty directory.
     *
     * @param string          $path   The path
     * @param Filesystem      $fs     The file system object
     * @param OutputInterface $output The output object
     */
    private function addEmptyDir($path, Filesystem $fs, OutputInterface $output)
    {
        if ($fs->exists($path)) {
            return;
        }

        $fs->mkdir($path);

        $output->writeln('Created the <comment>' . $path . '</comment> directory.');
    }

    /**
     * Adds a directory with a .gitignore file.
     *
     * @param string          $path   The path
     * @param Filesystem      $fs     The file system object
     * @param OutputInterface $output The output object
     */
    private function addIgnoredDir($path, Filesystem $fs, OutputInterface $output)
    {
        $this->addEmptyDir($path, $fs, $output);

        if ($fs->exists($path . '/.gitignore')) {
            return;
        }

        $fs->dumpFile(
            $path . '/.gitignore',
            "# Create the folder and ignore its content\n*\n!.gitignore\n"
        );

        $output->writeln('Added the <comment>' . $path . '/.gitignore</comment> file.');
    }
}
