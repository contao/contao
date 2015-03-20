<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\LockHandler;

/**
 * Installs the required Contao directories.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $rootDir;

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler('contao:install');

        // Set the lock
        if (!$lock->lock()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        $this->setOutput($output);
        $this->setRootDir(dirname($this->getContainer()->getParameter('kernel.root_dir')));

        $this->addContaoDirectories();

        // Release the lock
        $lock->release();

        return 0;
    }

    /**
     * Sets the output object.
     *
     * @param OutputInterface $output The output object.
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Sets the root directory.
     *
     * @param string $rootDir The root directory
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Adds the Contao directories.
     */
    public function addContaoDirectories()
    {
        self::addEmptyDir('files');
        self::addEmptyDir('system');
        self::addEmptyDir('templates');
        self::addEmptyDir('web/system');

        self::addIgnoredDir('assets/css');
        self::addIgnoredDir('assets/images');
        self::addIgnoredDir('assets/js');
        self::addIgnoredDir('system/cache');
        self::addIgnoredDir('system/config');
        self::addIgnoredDir('system/logs');
        self::addIgnoredDir('system/modules');
        self::addIgnoredDir('system/themes');
        self::addIgnoredDir('system/tmp');
        self::addIgnoredDir('web/share');
        self::addIgnoredDir('web/system/cron');
    }

    /**
     * Adds an empty directory.
     *
     * @param string $path The path
     */
    private function addEmptyDir($path)
    {
        $fs = new Filesystem();

        if ($fs->exists($this->rootDir . "/$path")) {
            return;
        }

        $fs->mkdir($this->rootDir . "/$path");

        $this->output->writeln("Created the <comment>$path</comment> directory.");
    }

    /**
     * Adds a directory with a .gitignore file.
     *
     * @param string $path The path
     */
    private function addIgnoredDir($path)
    {
        $fs = new Filesystem();

        self::addEmptyDir($path);

        if ($fs->exists($this->rootDir . "/$path/.gitignore")) {
            return;
        }

        $fs->dumpFile(
            $this->rootDir . "/$path/.gitignore",
            "# Create the folder and ignore its content\n*\n!.gitignore\n"
        );

        $this->output->writeln("Added the <comment>$path/.gitignore</comment> file.");
    }
}
