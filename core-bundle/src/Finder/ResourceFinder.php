<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Finder;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoModuleBundle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

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
    private $paths = [];

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel The kernel object
     */
    public function __construct(KernelInterface $kernel)
    {
        foreach ($kernel->getBundles() as $bundle) {
            if (is_dir($path = $this->getResourcesPath($bundle))) {
                $this->paths[] = $path;
            }
        }
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
     * Returns the Contao resources path of a bundle.
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
