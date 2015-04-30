<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config;

use Symfony\Component\Finder\Finder;

/**
 * Creates a Finder object with the bundle paths set.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ResourceFinder
{
    /**
     * @var array
     */
    private $paths;

    /**
     * Constructor.
     *
     * @param string|array $paths A path or an array of paths where to look for resources
     */
    public function __construct($paths = [])
    {
        $this->paths = (array) $paths;
    }

    /**
     * Returns a Finder object with the resource paths set.
     *
     * @return Finder The Finder object
     */
    public function find()
    {
        return Finder::create()->in($this->paths);
    }

    /**
     * Appends the subpath to the resource paths and returns a Finder object.
     *
     * @param string $subpath The subpath
     *
     * @return Finder The Finder object
     */
    public function findIn($subpath)
    {
        return Finder::create()->in($this->getExistingSubpaths($subpath));
    }

    /**
     * Returns an array of existing subpaths.
     *
     * @param string $subpath The path to append
     *
     * @return array The subpaths array
     *
     * @throws \InvalidArgumentException If the subpath does not exist
     */
    private function getExistingSubpaths($subpath)
    {
        $paths = [];

        foreach ($this->paths as $path) {
            if (is_dir($dir = "$path/$subpath")) {
                $paths[] = $dir;
            }
        }

        if (empty($paths)) {
            throw new \InvalidArgumentException("The subpath $subpath does not exists.");
        }

        return $paths;
    }
}
