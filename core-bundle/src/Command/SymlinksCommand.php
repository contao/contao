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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Symlinks the public resources into the /web directory.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SymlinksCommand extends ContainerAwareCommand
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
            ->setName('contao:symlinks')
            ->setDescription('Symlinks the public resources into the /web directory')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lock = new LockHandler('contao:symlinks');

        // Set the lock
        if (!$lock->lock()) {
            $output->writeln('The command is already running in another process.');

            return 1;
        }

        $this->setOutput($output);
        $this->setRootDir(dirname($this->getContainer()->getParameter('kernel.root_dir')));

        $this->generateSymlinks();

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
     * Generates the symlinks in the web/ directory.
     */
    public function generateSymlinks()
    {
        $container  = $this->getContainer();
        $uploadPath = $container->getParameter('contao.upload_path');
        $fs         = new Filesystem();

        // Remove the base folders in the document root
        $fs->remove($this->rootDir . "/web/$uploadPath");
        $fs->remove($this->rootDir . '/web/system/modules');
        $fs->remove($this->rootDir . '/web/vendor');

        $this->symlinkFiles($uploadPath);

        // Symlink the public extension subfolders
        foreach ($container->get('contao.resource_provider')->getPublicFolders() as $path) {
            $this->symlink(str_repeat('../', substr_count($path, '/') + 1) . $path, "web/$path");
        }

        $this->symlinkThemes();

        // Symlink the assets and themes directory
        $this->symlink('../assets', 'web/assets');
        $this->symlink('../../system/themes', 'web/system/themes');
    }

    /**
     * Creates the file symlinks.
     *
     * @param string $uploadPath The upload path
     */
    private function symlinkFiles($uploadPath)
    {
        $finder = Finder::create()
            ->ignoreDotFiles(false)
            ->files()
            ->name('.public')
            ->in($this->rootDir . "/$uploadPath")
        ;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $path = $uploadPath . '/' . $file->getRelativePath();
            $this->symlink(str_repeat('../', substr_count($path, '/') + 1) . $path, "web/$path");
        }
    }

    /**
     * Creates the theme symlinks.
     */
    private function symlinkThemes()
    {
        $finder = $this->getContainer()->get('contao.resource_provider')->findIn('themes');

        /** @var SplFileInfo $fileObj */
        foreach ($finder->directories()->depth(0) as $fileObj) {
            $path = str_replace($this->rootDir . '/', '', $fileObj->getPathname());

            if (0 === strpos($path, 'system/modules/')) {
                continue;
            }

            $this->symlink('../../' . $path, 'system/themes/' . basename($path));
        }
    }

    /**
     * Generates a symlink.
     *
     * @param string $source The symlink name
     * @param string $target The symlink target
     */
    private function symlink($source, $target)
    {
        $this->validateSymlink($source, $target);

        $fs = new Filesystem();
        $fs->symlink($source, $this->rootDir . "/$target");

        $stat = lstat($this->rootDir . "/$target");

        // Try to fix the UID
        if (function_exists('lchown') && $stat['uid'] !== getmyuid()) {
            lchown($this->rootDir . "/$target", getmyuid());
        }

        // Try to fix the GID
        if (function_exists('lchgrp') && $stat['gid'] !== getmygid()) {
            lchgrp($this->rootDir . "/$target", getmygid());
        }

        $this->output->writeln("Added <comment>$target</comment> as symlink to <comment>$source</comment>.");
    }

    /**
     * Validates a symlink.
     *
     * @param string $source The symlink name
     * @param string $target The symlink target
     *
     * @throws \InvalidArgumentException If the source or target is invalid
     * @throws \LogicException           If the symlink cannot be created
     */
    private function validateSymlink($source, $target)
    {
        if ($source == '') {
            throw new \InvalidArgumentException('The symlink source must not be empty');
        }

        if ($target == '') {
            throw new \InvalidArgumentException('The symlink target must not be empty');
        }

        if (false !== strpos($target, '../')) {
            throw new \InvalidArgumentException('The symlink target must not be relative');
        }

        $fs = new Filesystem();

        if ($fs->exists($this->rootDir . "/$target") && !is_link($this->rootDir . "/$target")) {
            throw new \LogicException("The symlink target $target exists and is not a symlink");
        }
    }
}
