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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Locates files within the bundle resources paths.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BundleFileLocator implements FileLocatorInterface
{
    /**
     * @var FileLocatorInterface
     */
    protected $locator;

    /**
     * Constructor.
     *
     * @param KernelInterface $kernel The kernel object
     */
    public function __construct(KernelInterface $kernel)
    {
        $paths = [];

        foreach ($kernel->getBundles() as $bundle) {
            if (is_dir($path = $this->getResourcesPath($bundle))) {
                $paths[] = $path;
            }
        }

        $this->locator = new FileLocator($paths);
    }

    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = true)
    {
        return $this->locator->locate($name, $currentPath, $first);
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
}
