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
 * Aggregates several file locators into a single one.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class FileLocatorAggregate implements FileLocatorInterface
{
    /**
     * @var FileLocatorInterface[]
     */
    private $locators = [];

    /**
     * Constructor.
     *
     * @param array $locators An array of locators
     */
    public function __construct(array $locators)
    {
        foreach ($locators as $locator) {
            $this->add($locator);
        }
    }

    /**
     * Adds a file locator.
     *
     * @param FileLocatorInterface $locator The locator object
     */
    public function add(FileLocatorInterface $locator)
    {
        $this->locators[] = $locator;
    }

    /**
     * {@inheritdoc}
     */
    public function locate($name, $currentPath = null, $first = true)
    {
        $paths = [];

        foreach ($this->locators as $locator) {
            try {
                $files = $locator->locate($name, $currentPath, $first);

                if (is_array($files)) {
                    $paths = array_merge($paths, $files);
                } else {
                    $paths[] = $files;
                }
            } catch (\InvalidArgumentException $e) {
                continue;
            }
        }

        return $paths;
    }
}
