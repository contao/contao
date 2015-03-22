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
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = false)
    {
        $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR . $name;

        if (file_exists($cacheFile)) {
            return $first ? $cacheFile : [$cacheFile];
        }

        if (null !== $this->locator) {
            return $this->locator->locate($name, $currentPath, $first);
        }

        throw new \InvalidArgumentException(sprintf('The file "%s" does not exist (in: %s).', $name, $this->cacheDir));
    }
}
