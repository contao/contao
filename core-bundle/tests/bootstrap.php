<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

error_reporting(E_ALL);

$include = function ($file) {
    return file_exists($file) ? include $file : false;
};

// PhpStorm fix (see https://www.drupal.org/node/2597814)
if (!defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', __DIR__.'/../vendor/autoload.php');
}

if (
    false === ($loader = $include(__DIR__.'/../vendor/autoload.php'))
    && false === ($loader = $include(__DIR__.'/../../../autoload.php'))
) {
    echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL
        .'curl -sS https://getcomposer.org/installer | php'.PHP_EOL
        .'php composer.phar install'.PHP_EOL;

    exit(1);
}

// Autoload the fixture classes
$fixtureLoader = function ($class) {
    if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
        return;
    }

    if (false !== strpos($class, '\\') && 0 !== strncmp($class, 'Contao\\', 7)) {
        return;
    }

    if (0 === strncmp($class, 'Contao\\', 7)) {
        $class = substr($class, 7);
    }

    $file = strtr($class, '\\', '/');

    if (file_exists(__DIR__.'/Fixtures/library/'.$file.'.php')) {
        include_once __DIR__.'/Fixtures/library/'.$file.'.php';
        class_alias('Contao\Fixtures\\'.$class, 'Contao\\'.$class);
    }

    $namespaced = 'Contao\\'.$class;

    if (class_exists($namespaced) || interface_exists($namespaced) || trait_exists($namespaced)) {
        class_alias($namespaced, $class);
    }
};

spl_autoload_register($fixtureLoader, true, true);

return $loader;
