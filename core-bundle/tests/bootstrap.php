<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

error_reporting(E_ALL);

$include = function ($file) {
    return file_exists($file) ? include $file : false;
};

if (
    false === ($loader = $include(__DIR__ . '/../vendor/autoload.php'))
    && false === ($loader = $include(__DIR__ . '/../../../autoload.php'))
) {
    echo 'You must set up the project dependencies, run the following commands:' . PHP_EOL
        . 'curl -sS https://getcomposer.org/installer | php' . PHP_EOL
        . 'php composer.phar install' . PHP_EOL;

    exit(1);
}

// Autoload the fixture classes
spl_autoload_register(function ($class) {
    if (class_exists($class, false)) {
        return;
    }

    if (0 === strncmp($class, 'Contao\\', 7)) {
        $class = substr($class, 7);
    }

    if (file_exists(__DIR__ . "/Fixtures/library/$class.php")) {
        include_once __DIR__ . "/Fixtures/library/$class.php";
        class_alias("Contao\\Fixtures\\$class", "Contao\\$class");
        class_alias("Contao\\Fixtures\\$class", $class);
    } elseif (file_exists(__DIR__ . "/../src/Resources/contao/library/Contao/$class.php")) {
        include_once __DIR__ . "/../src/Resources/contao/library/Contao/$class.php";
        class_alias("Contao\\$class", $class);
    }
});

/** @var Composer\Autoload\ClassLoader $loader */
$loader->addPsr4('Contao\\CoreBundle\\Test\\', __DIR__);
$loader->addPsr4('Contao\\TestBundle\\', __DIR__ . '/Fixtures/vendor/contao/test-bundle');

return $loader;
