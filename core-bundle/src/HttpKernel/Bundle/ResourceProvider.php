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
    /**
     * @var array
     */
    private $resourcesPaths;

    /**
     * @var array
     */
    private $publicFolders;

    /**
     * Constructor.
     *
     * @param array $resourcesPath An optional array of Contao resources paths
     * @param array $publicFolders An option array of public folders
     */
    public function __construct(array $resourcesPath = [], array $publicFolders = [])
    {
        $this->resourcesPaths = $resourcesPath;
        $this->publicFolders  = $publicFolders;
    }

    /**
     * Adds a resources path.
     *
     * @param string $path The resources path
     */
    public function addResourcesPath($path)
    {
        $this->resourcesPaths[] = $path;
    }

    /**
     * Adds public folders.
     *
     * @param array $paths The public folders
     */
    public function addPublicFolders(array $paths)
    {
        $this->publicFolders = array_merge($this->publicFolders, $paths);
    }

    /**
     * Returns the resources paths.
     *
     * @return array The resources paths
     */
    public function getResourcesPaths()
    {
        return $this->resourcesPaths;
    }

    /**
     * Returns the public folders.
     *
     * @return array The public folders
     */
    public function getPublicFolders()
    {
        return $this->publicFolders;
    }

    /**
     * Returns a Finder instance to find files or folders in the Contao resources.
     *
     * @param string $folder The folder name
     *
     * @return Finder|SplFileInfo[] The Finder instance
     *
     * @throws \RuntimeException If there are no Contao resources paths
     */
    public function findIn($folder)
    {
        if (empty($this->resourcesPaths)) {
            throw new \RuntimeException('No Contao resources paths available');
        }

        $finder = Finder::create()->ignoreDotFiles(true)->followLinks();

        foreach ($this->resourcesPaths as $path) {
            if (is_dir("$path/$folder")) {
                $finder->in("$path/$folder");
            }
        }

        return $finder;
    }

    /**
     * Returns a Finder instance to find files in the Contao resources.
     *
     * @param string $path The path
     *
     * @return Finder|SplFileInfo[] The Finder instance
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
