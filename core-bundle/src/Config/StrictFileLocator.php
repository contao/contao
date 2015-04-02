<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;

/**
 * Filters directories from a standard file locator.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class StrictFileLocator implements FileLocatorInterface
{
    /**
     * @var FileLocatorInterface
     */
    protected $locator;

    /**
     * Constructor.
     *
     * @param string|array $paths A path or an array of paths where to look for resources
     */
    public function __construct($paths = [])
    {
        $this->locator = new FileLocator($paths);
    }

    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = true)
    {
        $paths = $this->locator->locate($name, $currentPath, $first);

        if (is_array($paths)) {
            $paths = $this->filterDirectories($paths);

            if (empty($paths)) {
                throw new \InvalidArgumentException("The file $name does not exist in " . implode(', ', $paths) . '.');
            }
        } elseif (is_dir($paths)) {
            throw new \InvalidArgumentException("$name is a directory.");
        }

        return $paths;
    }

    /**
     * Removes directories from the paths array.
     *
     * @param array $paths The paths array
     *
     * @return array The filtered paths array
     */
    private function filterDirectories(array $paths)
    {
        $paths = array_filter($paths, function ($item) {
            return !is_dir($item);
        });

        return $paths;
    }
}
