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
 * ChainFileLocator tries to locate resources using a given set of FileLocatorInterface instances.
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
     * Adds a locator to the stack to find files.
     *
     * @param FileLocatorInterface $locator A file locator
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

        foreach ($this->locators as $locator) {
            try {
                $file = $locator->locate($name, $currentPath, $first);

                if ($first) {
                    return $file;
                }

                $files = array_merge(
                    $files,
                    is_array($file) ? $file : [$file]
                );
            } catch (\InvalidArgumentException $e) {
                // Try the next locator
            }
        }

        if (!$first) {
            return $files;
        }

        throw new \InvalidArgumentException(sprintf('No locator was able to find the file "%s".', $name));
    }
}
