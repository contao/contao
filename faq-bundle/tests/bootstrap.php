<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

error_reporting(E_ALL);

$include = function ($file) {
    return file_exists($file) ? include $file : false;
};

if (
    false === ($loader = $include(__DIR__.'/../vendor/autoload.php'))
    && false === ($loader = $include(__DIR__.'/../../vendor/autoload.php'))
    && false === ($loader = $include(__DIR__.'/../../../autoload.php'))
) {
    echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL
        .'curl -sS https://getcomposer.org/installer | php'.PHP_EOL
        .'php composer.phar install'.PHP_EOL;

    exit(1);
}

// Handle classes in the global namespace
$legacyLoader = function ($class) {
    if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
        return;
    }

    if (false !== strpos($class, '\\') && 0 !== strncmp($class, 'Contao\\', 7)) {
        return;
    }

    if (0 === strncmp($class, 'Contao\\', 7)) {
        $class = substr($class, 7);
    }

    $namespaced = 'Contao\\'.$class;

    if (class_exists($namespaced) || interface_exists($namespaced) || trait_exists($namespaced)) {
        class_alias($namespaced, $class);
    }
};

spl_autoload_register($legacyLoader, true, true);

return $loader;
