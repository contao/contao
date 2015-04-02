<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config;

use Symfony\Component\Config\FileLocatorInterface;

/**
 * Searches the cache paths for files.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class CacheFileLocator implements FileLocatorInterface
{
    /**
     * @var array
     */
    protected $paths;

    /**
     * Constructor.
     *
     * @param string|array $paths A path or an array of paths where to look for resources
     */
    public function __construct($paths = array())
    {
        $this->paths = (array) $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = true)
    {
        if ('' == $name) {
            throw new \InvalidArgumentException('An empty file name is not valid to be located.');
        }

        $paths = [];

        foreach ($this->paths as $path) {
            if (!file_exists($file = "$path/$name") || is_dir($file)) {
                continue;
            }

            if (true === $first) {
                return $file;
            }

            $paths[] = $file;
        }

        if (empty($paths)) {
            throw new \InvalidArgumentException("The file $name does not exist in " . implode(', ', $this->paths) . '.');
        }

        return array_values(array_unique($paths));
    }
}
