<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Finder;

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
     * @param array $paths The resources paths
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * Returns a new Finder object with the resource paths set.
     *
     * @param string $path An optional path
     *
     * @return Finder The Finder object
     */
    public function in($path = null)
    {
        $paths = $this->paths;

        if (null !== $path) {
            $paths = $this->getExistingSubpaths($path);
        }

        return Finder::create()->depth('== 0')->in($paths);
    }

    /**
     * Returns an array of existing subpaths.
     *
     * @param string $path The path to append
     *
     * @return array The subpaths array
     *
     * @throws \InvalidArgumentException If the subpath does not exist
     */
    private function getExistingSubpaths($path)
    {
        $paths = [];

        foreach ($this->paths as $key => $value) {
            if (is_dir($dir = "$value/$path")) {
                $paths[] = $dir;
            }
        }

        if (empty($paths)) {
            throw new \InvalidArgumentException("The subpath $path does not exists.");
        }

        return $paths;
    }
}
