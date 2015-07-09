<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle;

/**
 * Autoloads the Contao library classes.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class LibraryLoader
{
    /**
     * Registers the autoloader.
     */
    public static function register()
    {
        spl_autoload_register('Contao\\InstallationBundle\\LibraryLoader::load');
    }

    /**
     * Loads a Contao library class.
     *
     * @param string $class The class name
     */
    public static function load($class)
    {
        if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
            return;
        }

        $class    = str_replace('Contao\\', '', $class);
        $classDir = TL_ROOT . '/vendor/contao/core-bundle/src/Resources/contao/library/Contao';

        if (!file_exists($classDir . '/' . $class . '.php')) {
            return;
        }

        include $classDir . '/' . $class . '.php';
        class_alias('Contao\\' . $class, $class);
    }
}
