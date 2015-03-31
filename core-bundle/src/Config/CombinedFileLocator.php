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
 * CombinedFileLocator tries to locate a combined file, otherwise delegates to the next file locator.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class CombinedFileLocator implements FileLocatorInterface
{
    private $cacheDir;
    private $locator;

    /**
     * Constructor.
     *
     * @param string               $cacheDir The path where combined files are stored
     * @param FileLocatorInterface $locator  A file locator to locate files if cache is not found
     */
    public function __construct($cacheDir, FileLocatorInterface $locator = null)
    {
        $this->cacheDir = $cacheDir;
        $this->locator  = $locator;
    }

    /**
     * Returns a full path for a given file name.
     *
     * @param string      $name        The file name to locate
     * @param string|null $currentPath The current path. Only used for the non-cache locator.
     * @param bool        $first       If true and no cache file exists, the exception will be thrown.
     *
     * @return string|array The full path to the file or an array of file paths
     *
     * @throws \InvalidArgumentException When file is not found
     */
    public function locate($name, $currentPath = null, $first = false)
    {
        // FIXME: we should inject this configuration
        if (!\Config::get('bypassCache')) {
            $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR . $name;

            if (file_exists($cacheFile)) {
                return $first ? $cacheFile : [$cacheFile];
            }
        }

        // If $first is true, the cached file was wanted so don't ask the locator
        if (!$first && null !== $this->locator) {
            return $this->locator->locate($name, $currentPath, false);
        }

        throw new \InvalidArgumentException(sprintf('The file "%s" does not exist (in: %s).', $name, $this->cacheDir));
    }
}
