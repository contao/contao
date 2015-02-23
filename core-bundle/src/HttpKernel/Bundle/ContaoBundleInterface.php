<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Contao bundle interface.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
interface ContaoBundleInterface extends BundleInterface
{
    /**
     * Returns the folders which shall be made public.
     *
     * @return array The public folders array
     */
    public function getPublicFolders();

    /**
     * Returns the path to the Contao resources directory.
     *
     * @return string The path to the resources directory
     */
    public function getContaoResourcesPath();
}
