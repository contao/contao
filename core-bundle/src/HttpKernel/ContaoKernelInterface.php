<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Contao kernel interface.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface ContaoKernelInterface extends KernelInterface
{
    /**
     * Adds the autoload bundles to the bundles array.
     *
     * @param array $bundles The bundles array
     */
    public function addAutoloadBundles(&$bundles);

    /**
     * Writes the bundle cache.
     */
    public function writeBundleCache();

    /**
     * Loads the bundle cache.
     */
    public function loadBundleCache();

    /**
     * Returns an array of all Contao bundles.
     *
     * @return ContaoBundleInterface[] The Contao bundles array
     */
    public function getContaoBundles();
}
