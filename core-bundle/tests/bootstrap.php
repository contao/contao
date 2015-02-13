<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

error_reporting(E_ALL);

// Define a custom System class via eval() so it does not interfere with the IDE
eval(<<<HEREDOC
class System
{
    public static function getReferer()
    {
        return '/foo/bar';
    }
}
HEREDOC
);

// Define a custom Frontend class via eval() so it does not interfere with the IDE
eval(<<<HEREDOC
use Symfony\Component\HttpFoundation\Response;

class Frontend
{
    public static function indexPageIfApplicable(Response \$objResponse)
    {
        return true;
    }

    public static function getResponseFromCache()
    {
        return new Response();
    }
}
HEREDOC
);

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

/** @var Composer\Autoload\ClassLoader $loader */
$loader->addPsr4('Contao\\CoreBundle\\Test\\', __DIR__);

return $loader;
