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
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Creates FileLocator objects.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FileLocatorFactory implements FileLocatorFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public static function create(array $paths)
    {
        return new FileLocator($paths);
    }

    /**
     * {@inheritdoc}
     */
    public static function createWithBundlePaths(KernelInterface $kernel)
    {
        $paths = [];

        foreach ($kernel->getBundles() as $bundle) {
            if (is_dir($path = self::getResourcesPath($bundle))) {
                $paths[] = $path;
            }
        }

        return new FileLocator($paths);
    }

    /**
     * {@inheritdoc}
     */
    public static function createWithCachePath($cachePath)
    {
        return new StrictFileLocator($cachePath);
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
