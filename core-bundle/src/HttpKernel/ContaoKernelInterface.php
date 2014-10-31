<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\HttpKernel;

use Contao\CoreBundle\HttpKernel\Bundle\ContaoBundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Autoloads the bundles
 *
 * @author Leo Feyer <https://contao.org>
 */
interface ContaoKernelInterface extends KernelInterface
{
    /**
     * Adds the autoload bundles
     *
     * @param array $bundles The bundles array
     */
    public function addAutoloadBundles(&$bundles);

    /**
     * Return all Contao bundles as array
     *
     * @return ContaoBundleInterface[] The Contao bundles
     */
    public function getContaoBundles();

    /**
     * Writes the bundle cache
     */
    public function writeBundleCache();

    /**
     * Loads the bundle cache
     */
    public function loadBundleCache();
}
