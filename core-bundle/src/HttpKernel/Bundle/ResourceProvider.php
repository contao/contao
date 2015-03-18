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

/**
 * Provides information about Contao resources and public folders.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ResourceProvider
{
    private $contaoResources;
    private $publicFolders;

    /**
     * Constructor.
     *
     * @param array $contaoResources An optional array of Contao resource paths
     * @param array $publicFolders   An option array of public folders
     */
    public function __construct(array $contaoResources = [], array $publicFolders = [])
    {
        $this->contaoResources = $contaoResources;
        $this->publicFolders   = $publicFolders;
    }

    /**
     * Adds resource path of a bundle
     *
     * @param string $path The resources path
     */
    public function addResourcesPath($path)
    {
        $this->contaoResources[] = $path;
    }

    /**
     * Adds public folders
     *
     * @param array $paths The public folders
     */
    public function addPublicFolders(array $paths)
    {
        $this->publicFolders = array_merge($this->publicFolders, $paths);
    }

    /**
     * Returns all Contao resources paths
     *
     * @return array The resources paths
     */
    public function getResourcesPaths()
    {
        return $this->contaoResources;
    }

    /**
     * Returns the public folders
     *
     * @return array The public folders
     */
    public function getPublicFolders()
    {
        return $this->publicFolders;
    }

    /**
     * Returns a Finder instance to find files or folders in the Contao resources
     *
     * @param string $folder A folder name in the resources
     *
     * @return Finder|SplFileInfo[] A Finder instance
     *
     * @throws \UnderflowException If no Contao resources paths are available
     */
    public function findIn($folder)
    {
        if (empty($this->contaoResources)) {
            throw new \UnderflowException('No Contao resources paths available.');
        }

        $finder = Finder::create()->ignoreDotFiles(true)->followLinks();

        foreach ($this->contaoResources as $path) {
            $finder->in($path . '/' . $folder);
        }

        return $finder;
    }

    /**
     * Returns a Finder instance to find files in the given Contao resources folder
     *
     * @param string $path A file name to be found in a the Contao resources folder.
     *
     * @return Finder|SplFileInfo[] A Finder instance
     *
     * @throws \UnderflowException If no Contao resources paths are available
     */
    public function findFiles($path)
    {
        $folder   = dirname($path);
        $fileName = basename($path);

        $finder = $this->findIn($folder);
        $finder->files()->name($fileName)->depth(0);

        return $finder;
    }
}
