<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Converts a Contao module in system/modules into a bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoModuleBundle extends Bundle implements ContaoBundleInterface
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var array
     */
    protected $publicDirs;

    /**
     * Sets the module name and application root directory.
     *
     * @param string $name    The module name
     * @param string $rootDir The application root directory
     */
    public function __construct($name, $rootDir)
    {
        $this->name    = $name;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicFolders()
    {
        if (null === $this->publicDirs) {
            $this->publicDirs = $this->findPublicFolders();
        }

        return $this->publicDirs;
    }

    /**
     * {@inheritdoc}
     */
    public function getContaoResourcesPath()
    {
        return $this->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if (null === $this->path) {
            $this->path = dirname($this->rootDir) . '/system/modules/' . $this->name;
        }

        return $this->path;
    }

    /**
     * Finds the public folders.
     *
     * @return array The public folders
     */
    protected function findPublicFolders()
    {
        $dirs  = [];
        $files = $this->findHtaccessFiles();

        /** @var SplFileInfo[] $files */
        foreach ($files as $file) {
            $htaccess = new HtaccessAnalyzer($file);

            if ($htaccess->grantsAccess()) {
                $dirs[] = $file->getPath();
            }
        }

        return $dirs;
    }

    /**
     * Finds the .htaccess files in the Contao directory.
     *
     * @return Finder The finder object
     */
    protected function findHtaccessFiles()
    {
        return Finder::create()
            ->files()
            ->name('.htaccess')
            ->ignoreDotFiles(false)
            ->in($this->getContaoResourcesPath())
        ;
    }
}
