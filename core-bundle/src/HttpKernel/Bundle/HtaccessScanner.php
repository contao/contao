<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 *
 *
 * @author Leo Feyer <https://contao.org>
 */
class HtaccessScanner
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * Sets the root directory.
     *
     * @param string $rootDir The application root directory
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * Finds the public folders.
     *
     * @return array The public folders
     */
    public function findPublicFolders()
    {
        $dirs  = [];
        $files = $this->findHtaccessFiles();

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($this->isPublicFolder($file)) {
                $dirs[] = $file->getPath();
            }
        }

        return $dirs;
    }

    /**
     * Finds the .htaccess files in the root directory.
     *
     * @return Finder The finder object
     */
    protected function findHtaccessFiles()
    {
        return Finder::create()
            ->files()
            ->name('.htaccess')
            ->ignoreDotFiles(false)
            ->in($this->rootDir)
        ;
    }

    /**
     * Checks whether the .htaccess file grants access via HTTP.
     *
     * @param SplFileInfo $file The file object
     *
     * @return bool True if the .htaccess file grants access via HTTP
     */
    protected function isPublicFolder(SplFileInfo $file)
    {
        $content = array_filter(file($file));

        foreach ($content as $line) {
            if ($this->hasRequireGranted($line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scans a line for an access definition.
     *
     * @param string $line The line
     *
     * @return bool True if the line has an access definition
     */
    protected function hasRequireGranted($line)
    {
        // Ignore comments
        if (0 === strncmp('#', trim($line), 1)) {
            return false;
        }

        if (false !== stripos($line, 'Allow from all')) {
            return true;
        }

        if (false !== stripos($line, 'Require all granted')) {
            return true;
        }

        return false;
    }
}
