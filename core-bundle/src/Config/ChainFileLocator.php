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

/**
 * Tries to locate resources using a given set of file locators.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ChainFileLocator implements FileLocatorInterface
{
    /**
     * @var FileLocatorInterface[]
     */
    private $locators = [];

    /**
     * Adds a file locator.
     *
     * @param FileLocatorInterface $locator The file locator
     */
    public function addLocator(FileLocatorInterface $locator)
    {
        $this->locators[] = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = true)
    {
        foreach ($this->locators as $locator) {
            try {
                return $locator->locate($name, $currentPath, $first);
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        throw new \InvalidArgumentException("No locator was able to find $name");
    }
}
