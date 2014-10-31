<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\HttpKernel\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Configures a Contao module bundle.
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoModuleBundle extends Bundle implements ContaoBundleInterface
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * Sets the module name and application root directory.
     *
     * @param string $name    The module name
     * @param string $rootDir The application root directory
     */
    public function __construct($name, $rootDir)
    {
        $this->name    = $name;
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicFolders()
    {
        return [
            $this->getContaoResourcesPath() . '/assets'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getContaoResourcesPath()
    {
        return $this->getPath();
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        if (null === $this->path) {
            $this->path = dirname($this->rootDir) . '/system/modules/' . $this->name;
        }

        return $this->path;
    }
}
