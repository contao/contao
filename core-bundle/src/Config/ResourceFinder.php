<?php

declare(strict_types=1);

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
 */
class ResourceFinder implements ResourceFinderInterface
{
    /**
     * @var array
     */
    private $paths;

    /**
     * @param string|array $paths
     */
    public function __construct($paths = [])
    {
        $this->paths = (array) $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function find(): Finder
    {
        return Finder::create()->in($this->paths);
    }

    /**
     * {@inheritdoc}
     */
    public function findIn($subpath): Finder
    {
        return Finder::create()->in($this->getExistingSubpaths($subpath));
    }

    /**
     * Returns an array of existing subpaths.
     *
     * @param string $subpath
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    private function getExistingSubpaths(string $subpath): array
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
