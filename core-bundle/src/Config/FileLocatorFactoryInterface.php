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
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Interface for file locator factories.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface FileLocatorFactoryInterface
{
    /**
     * Creates a FileLocator object.
     *
     * @param array $paths An array of paths
     *
     * @return FileLocatorInterface The FileLocator instance
     */
    public static function create(array $paths);

    /**
     * Creates a FileLocator object passing the bundle paths.
     *
     * @param KernelInterface $kernel The kernel object
     *
     * @return FileLocatorInterface The FileLocator instance
     */
    public static function createWithBundlePaths(KernelInterface $kernel);

    /**
     * Creates a FileLocator object passing the cache directory.
     *
     * @param string $cachePath The cache path
     *
     * @return FileLocatorInterface The FileLocator instance
     */
    public static function createWithCachePath($cachePath);
}
