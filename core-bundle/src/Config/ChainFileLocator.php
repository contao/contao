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
    public function locate($name, $currentPath = null, $first = false)
    {
        $files = [];

        foreach ($this->getLocators($first) as $locator) {
            try {
                $file = $locator->locate($name, $currentPath, $first);

                if (true === $first) {
                    return $file;
                }

                $files = array_merge($files, (is_array($file) ? $file : [$file]));
            } catch (\InvalidArgumentException $e) {
                // Try the next locator
            }
        }

        if (false === $first) {
            return $files;
        }

        throw new \InvalidArgumentException("No locator was able to find $name");
    }

    /**
     * Reverses the locator order so that higher priority locators overwrite lower priority ones.
     *
     * @param bool $first Whether to return the first occurrence or an array of filenames
     *
     * @return FileLocatorInterface[] The locators array
     */
    private function getLocators($first)
    {
        if (false === $first) {
            return array_reverse($this->locators);
        }

        return $this->locators;
    }
}
