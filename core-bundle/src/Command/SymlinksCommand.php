<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Command;

use Contao\CoreBundle\Analyzer\HtaccessAnalyzer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Symlinks the public resources into the /web directory.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class SymlinksCommand extends LockedCommand implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface|null
     */
    private $container;

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
            ->setName('contao:symlinks')
            ->setDescription('Symlinks the public resources into the /web directory')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeLocked(InputInterface $input, OutputInterface $output)
    {
        $this->generateSymlinks(dirname($this->container->getParameter('kernel.root_dir')), $output);

        return 0;
    }

    /**
     * Generates the symlinks in the web/ directory.
     *
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    public function generateSymlinks($rootDir, OutputInterface $output)
    {
        $fs         = new Filesystem();
        $uploadPath = $this->container->getParameter('contao.upload_path');

        // Remove the base folders in the document root
        $fs->remove("$rootDir/web/$uploadPath");
        $fs->remove("$rootDir/web/system/modules");
        $fs->remove("$rootDir/web/vendor");

        $this->symlinkFiles($uploadPath, $rootDir, $output);
        $this->symlinkModules($rootDir, $output);
        $this->symlinkThemes($rootDir, $output);

        // Symlink the assets and themes directory
        $this->symlink('../assets', 'web/assets', $rootDir, $output);
        $this->symlink('../../system/themes', 'web/system/themes', $rootDir, $output);
    }

    /**
     * Creates the file symlinks.
     *
     * @param string          $uploadPath The upload path
     * @param string          $rootDir    The root directory
     * @param OutputInterface $output     The output object
     */
    private function symlinkFiles($uploadPath, $rootDir, OutputInterface $output)
    {
        $finder = $this->findIn('.public', "$rootDir/$uploadPath");

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $this->relativeSymlink($uploadPath . '/' . $file->getRelativePath(), $rootDir, $output);
        }
    }

    /**
     * Creates symlinks for the public module subfolders.
     *
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    private function symlinkModules($rootDir, OutputInterface $output)
    {
        $files = $this->findIn('.htaccess', "$rootDir/system/modules");

        /** @var SplFileInfo[] $files */
        foreach ($files as $file) {
            $htaccess = new HtaccessAnalyzer($file);

            if (!$htaccess->grantsAccess()) {
                continue;
            }

            $this->relativeSymlink($file->getPath(), $rootDir, $output);
        }
    }

    /**
     * Creates the theme symlinks.
     *
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    private function symlinkThemes($rootDir, OutputInterface $output)
    {
        try {
            $themes = $this->container->get('contao.resource_locator')->locate('themes');
        } catch (\InvalidArgumentException $e) {
            return; // no themes found
        }

        foreach ($themes as $dir) {
            $path = str_replace("$rootDir/", '', $dir);

            if (0 === strpos($path, 'system/modules/')) {
                continue;
            }

            $this->symlink("../../$path", 'system/themes/' . basename($path), $rootDir, $output);
        }
    }

    /**
     * Generates a symlink relative to the given path.
     *
     * @param string          $path    The path
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    private function relativeSymlink($path, $rootDir, OutputInterface $output)
    {
        $this->symlink(str_repeat('../', substr_count($path, '/') + 1) . $path, "web/$path", $rootDir, $output);
    }

    /**
     * Generates a symlink.
     *
     * @param string          $source  The symlink name
     * @param string          $target  The symlink target
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    private function symlink($source, $target, $rootDir, OutputInterface $output)
    {
        $this->validateSymlink($source, $target, $rootDir);

        $fs = new Filesystem();
        $fs->symlink($source, "$rootDir/$target");

        $stat = lstat("$rootDir/$target");

        // Try to fix the UID
        if (function_exists('lchown') && $stat['uid'] !== getmyuid()) {
            lchown("$rootDir/$target", getmyuid());
        }

        // Try to fix the GID
        if (function_exists('lchgrp') && $stat['gid'] !== getmygid()) {
            lchgrp("$rootDir/$target", getmygid());
        }

        $output->writeln("Added <comment>$target</comment> as symlink to <comment>$source</comment>.");
    }

    /**
     * Validates a symlink.
     *
     * @param string $source  The symlink name
     * @param string $target  The symlink target
     * @param string $rootDir The root directory
     *
     * @throws \InvalidArgumentException If the source or target is invalid
     * @throws \LogicException           If the symlink cannot be created
     */
    private function validateSymlink($source, $target, $rootDir)
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

        if ($fs->exists("$rootDir/$target") && !is_link("$rootDir/$target")) {
            throw new \LogicException("The symlink target $target exists and is not a symlink");
        }
    }

    /**
     * Returns a finder instance to find files in the given path.
     *
     * @param string $file The file name
     * @param string $path The absolute path
     *
     * @return Finder The finder instance
     */
    private function findIn($file, $path)
    {
        return Finder::create()->ignoreDotFiles(false)->files()->name($file)->in($path);
    }
}
