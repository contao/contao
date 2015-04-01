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
     * @param array $paths An array of paths with bundle name as key.
     */
    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = false)
    {
        // FIXME: this is almost the same code as in Symfony\Component\Config\FileLocator.
        // We should try to use the original class now that we have the static factory method.
        if ('' === $name) {
            throw new \InvalidArgumentException('An empty file name is not valid to be located.');
        }

        $paths = [];

        foreach ($this->paths as $bundle => $path) {
            if (!file_exists($file = "$path/$name")) {
                continue;
            }

            if (true === $first) {
                return $file;
            }

            $paths[$bundle] = $file;
        }

        if (!$paths) {
            throw new \InvalidArgumentException("The file $name does not exist in " . implode(', ', $this->paths));
        }

        return $paths;
    }

    /**
     * Creates a FileLocator instance from the kernel bundles.
     *
     * @param KernelInterface $kernel The kernel object
     *
     * @return static The FileLocator instance
     */
    public static function createFromKernelBundles(KernelInterface $kernel)
    {
        $paths = [];

        foreach ($kernel->getBundles() as $bundle) {
            if (is_dir($path = self::getResourcesPath($bundle))) {
                $paths[$bundle->getName()] = $path;
            }
        }

        return new static($paths);
    }

    /**
     * Returns the Contao resources path of a bundle.
     *
     * @param BundleInterface $bundle The bundle object
     *
     * @return string The resources path
     */
    private static function getResourcesPath(BundleInterface $bundle)
    {
        if ($bundle instanceof ContaoModuleBundle) {
            return $bundle->getPath();
        }

        return $bundle->getPath() . '/Resources/contao';
    }
}
