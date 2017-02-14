<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
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
class ResourceFinder implements ResourceFinderInterface
{
    /**
     * @var array
     */
    private $paths;

    /**
     * Constructor.
     *
     * @param string|array $paths
     */
    public function __construct($paths = [])
    {
        $this->paths = (array) $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function find()
    {
        return Finder::create()->in($this->paths);
    }

    /**
     * {@inheritdoc}
     */
    public function findIn($subpath)
    {
        return Finder::create()->in($this->getExistingSubpaths($subpath));
    }

    /**
     * Returns an array of existing subpaths.
     *
     * @param string $subpath
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function getExistingSubpaths($subpath)
    {
        $paths = [];

        foreach ($this->paths as $path) {
            if (is_dir($dir = $path.'/'.$subpath)) {
                $paths[] = $dir;
            }
        }

        if (empty($paths)) {
            throw new \InvalidArgumentException(sprintf('The subpath "%s" does not exists.', $subpath));
        }

        return $paths;
    }
}
