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
 * Finds Contao resources in the bundle directories.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class FileLocator implements FileLocatorInterface
{
    /**
     * @var array
     */
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
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = false)
    {
        if ('' === $name) {
            throw new \InvalidArgumentException('An empty file name is not valid to be located.');
        }

        $filepaths = [];

        foreach ($this->paths as $bundle => $path) {
            if (!file_exists($file = "$path/$name")) {
                continue;
            }

            if (true === $first) {
                return $file;
            }

            $filepaths[$bundle] = $file;
        }

        if (false === $first) {
            return $filepaths;
        }

        throw new \InvalidArgumentException("The file $name does not exist in " . implode(', ', $this->paths));
    }

    /**
     * Finds the resources paths in an array of bundles.
     *
     * @param BundleInterface[] $bundles The bundles array
     *
     * @return array The paths array
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
     * Returns the Contao resources path from a bundle.
     *
     * @param BundleInterface $bundle The bundle object
     *
     * @return string The resources path
     */
    private function getResourcesPath(BundleInterface $bundle)
    {
        if ($bundle instanceof ContaoModuleBundle) {
            return $bundle->getPath();
        }

        return $bundle->getPath() . '/Resources/contao';
    }
}
