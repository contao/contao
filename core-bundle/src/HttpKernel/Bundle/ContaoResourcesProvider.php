<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

class ContaoResourcesProvider
{
    private $contaoResources = [];
    private $publicFolders = [];
    private $rootDir;


    public function __construct($kernelRootDir)
    {
        // We need relative paths starting from "TL_ROOT"
        $this->rootDir = dirname($kernelRootDir) . '/';
    }

    public function addResourcesPath($bundleName, $path)
    {
        $this->contaoResources[$bundleName] = $path;
    }

    public function addPublicFolders(array $paths)
    {
        foreach ($paths as $path) {
            if (strpos($path, '../') !== false) {
                $path = realpath($path);
            }

            $this->publicFolders[] = str_replace($this->rootDir, '', $path);
        }
    }

    public function getResourcesPaths()
    {
        return $this->contaoResources;
    }

    public function getPublicFolders()
    {
        return $this->publicFolders;
    }
}
