<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\Config;

use Symfony\Component\Config\FileLocatorInterface;

/**
 * Void file locator which always returns an empty array.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class VoidFileLocator implements FileLocatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = true)
    {
        return [];
    }
}
