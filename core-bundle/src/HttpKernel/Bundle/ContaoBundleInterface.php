<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Adds specific methods to the bundle interface.
 *
 * @author Leo Feyer <https://contao.org>
 */
interface ContaoBundleInterface extends BundleInterface
{
    /**
     * Returns the folders which shall be made public.
     *
     * @return array The folders which shall be symlinked
     */
    public function getPublicFolders();

    /**
     * Returns the path to the Contao resources directory.
     *
     * @return string The path to the resources directory
     */
    public function getContaoResourcesPath();
}
