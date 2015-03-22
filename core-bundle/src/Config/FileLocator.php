<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * FileLoader finds Contao resources in bundle directories.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class FileLocator implements FileLocatorInterface
{
    private $paths;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel A KernelInterface instance
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->paths = $this->findResourcesPaths($kernel->getBundles());
    }

    /**
     * Returns full paths for a given file name with bundle name as array key.
     *
     * @param string      $name        The file name to locate
     * @param string|null $currentPath Not used
     * @param bool        $first       Whether to return the first occurrence or an array of filenames
     *
     * @return string|array The full path to the file or an array of file paths
     *
     * @throws \InvalidArgumentException When file is not found and $first is true
     */
    public function locate($name, $currentPath = null, $first = false)
    {
        if ('' == $name) {
            throw new \InvalidArgumentException('An empty file name is not valid to be located.');
        }

        $filepaths = [];
        foreach ($this->paths as $bundle => $path) {
            if (file_exists($file = $path . DIRECTORY_SEPARATOR . $name)) {
                if (true === $first) {
                    return $file;
                }
                $filepaths[$bundle] = $file;
            }
        }

        if (!$first) {
            return $filepaths;
        }

        throw new \InvalidArgumentException(sprintf('The file "%s" does not exist (in: %s).', $name, implode(', ', $this->paths)));
    }

    /**
     * @param BundleInterface[] $bundles
     *
     * @return array
     */
    private function findResourcesPaths(array $bundles)
    {
        $paths = [];

        foreach ($bundles as $bundle) {
            if (is_dir($path = $this->getResourcesPath($bundle))) {
                $paths[$bundle->getName()] = $path;
            }
        }

        return $paths;
    }

    /**
     * Get the Contao resources path from a bundle
     *
     * @param BundleInterface $bundle
     *
     * @return string
     */
    private function getResourcesPath(BundleInterface $bundle)
    {
        if ($bundle instanceof ContaoModuleBundle) {
            return $bundle->getPath();
        } else {
            return $bundle->getPath() . '/Resources/contao';
        }
    }
}
