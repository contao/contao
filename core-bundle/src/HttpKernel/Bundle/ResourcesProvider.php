<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

/**
 * Provides information about Contao resources and public folders.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ResourcesProvider
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
     * @param string $bundleName The bundle name
     * @param string $path       The resources path
     */
    public function addResourcesPath($bundleName, $path)
    {
        $this->contaoResources[$bundleName] = $path;
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
     * Returns all bundle names
     *
     * @return array The bundle names
     */
    public function getBundleNames()
    {
        return array_keys($this->contaoResources);
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
}
