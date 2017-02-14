<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\ClassLoader;

/**
 * Autoloads the Contao library classes.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class LibraryLoader
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * Constructor.
     *
     * @param string $rootDir
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;

        define('TL_MODE', 'BE');
        define('TL_ROOT', dirname($rootDir));
    }

    /**
     * Registers the autoloader.
     */
    public function register()
    {
        spl_autoload_register([$this, 'load']);
    }

    /**
     * Loads a Contao library class.
     *
     * @param string $class
     */
    public function load($class)
    {
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $class = str_replace('Contao\\', '', $class);
        $dir = $this->rootDir.'/../vendor/contao/core-bundle/src/Resources/contao';
        $file = strtr($class, '\\', '/').'.php';

        if (!file_exists($dir.'/library/Contao/'.$file)) {
            return;
        }

        $fqcn = 'Contao\\'.$class;

        // The fully qualified class name might have been autoloaded by Composer
        if (!class_exists($fqcn, false) && !interface_exists($fqcn, false) && !trait_exists($fqcn, false)) {
            include $dir.'/library/Contao/'.$file;
        }

        class_alias($fqcn, $class);
    }
}
