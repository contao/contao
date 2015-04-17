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
use Symfony\Component\Filesystem\Exception\IOException;
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
            ->setDescription('Symlinks the public resources into the /web directory.')
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
        $this->symlink('assets', 'web/assets', $rootDir, $output);
        $this->symlink('system/themes', 'web/system/themes', $rootDir, $output);
        $this->symlink('app/logs', 'system/logs', $rootDir, $output);
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
        $this->createSymlinksFromFinder(
            $this->findIn("$rootDir/$uploadPath")->files()->name('.public'),
            $uploadPath,
            $rootDir,
            $output
        );
    }

    /**
     * Creates symlinks for the public module subfolders.
     *
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    private function symlinkModules($rootDir, OutputInterface $output)
    {
        $filter = function (SplFileInfo $file) {
            return HtaccessAnalyzer::create($file)->grantsAccess();
        };

        $this->createSymlinksFromFinder(
            $this->findIn("$rootDir/system/modules")->files()->filter($filter)->name('.htaccess'),
            'system/modules',
            $rootDir,
            $output
        );
    }

    /**
     * Creates the theme symlinks.
     *
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    private function symlinkThemes($rootDir, OutputInterface $output)
    {
        /** @var SplFileInfo[] $themes */
        $themes = $this->container->get('contao.resource_finder')->findIn('themes')->depth(0)->directories();

        foreach ($themes as $theme) {
            $path = str_replace("$rootDir/", '', $theme->getPathname());

            if (0 === strpos($path, 'system/modules/')) {
                continue;
            }

            $this->symlink($path, 'system/themes/' . basename($path), $rootDir, $output);
        }
    }

    /**
     * Generates symlinks from a Finder object.
     *
     * @param Finder          $finder  The finder object
     * @param string          $prepend The path to prepend
     * @param string          $rootDir The root directory
     * @param OutputInterface $output  The output object
     */
    private function createSymlinksFromFinder(Finder $finder, $prepend, $rootDir, OutputInterface $output)
    {
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $path = rtrim($prepend . '/' . $file->getRelativePath(), '/');
            $this->symlink($path, "web/$path", $rootDir, $output);
        }
    }

    /**
     * Generates a symlink.
     *
     * The method will try to generate relative symlinks and fall back to generating
     * absolute symlinks if relative symlinks are not supported (see #208).
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

        try {
            $fs->symlink(rtrim($fs->makePathRelative($source, dirname($target)), '/'), "$rootDir/$target");
        } catch (IOException $e) {
            $fs->symlink("$rootDir/$source", "$rootDir/$target");
        }

        $this->fixSymlinkPermissions($target, $rootDir);

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
        if ('' === $source) {
            throw new \InvalidArgumentException('The symlink source must not be empty.');
        }

        if ('' === $target) {
            throw new \InvalidArgumentException('The symlink target must not be empty.');
        }

        if (false !== strpos($target, '../')) {
            throw new \InvalidArgumentException('The symlink target must not be relative.');
        }

        $fs = new Filesystem();

        if ($fs->exists("$rootDir/$target") && !is_link("$rootDir/$target")) {
            throw new \LogicException("The symlink target $target exists and is not a symlink.");
        }
    }

    /**
     * Fixes the symlink permissions.
     *
     * @param string $target  The symlink target
     * @param string $rootDir The root directory
     */
    private function fixSymlinkPermissions($target, $rootDir)
    {
        $stat = lstat("$rootDir/$target");

        // Try to fix the UID
        if (function_exists('lchown') && $stat['uid'] !== getmyuid()) {
            lchown("$rootDir/$target", getmyuid());
        }

        // Try to fix the GID
        if (function_exists('lchgrp') && $stat['gid'] !== getmygid()) {
            lchgrp("$rootDir/$target", getmygid());
        }
    }

    /**
     * Returns a finder instance to find files in the given path.
     *
     * @param string $path The path
     *
     * @return Finder The finder object
     */
    private function findIn($path)
    {
        return Finder::create()->ignoreDotFiles(false)->in($path);
    }
}
