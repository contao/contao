<?php

/**
 * This file is part of Contao.
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

// Define a custom Dbafs class via eval() so it does not interfere with the IDE
eval(<<<HEREDOC
namespace Contao;

class Dbafs
{
    public static function syncFiles()
    {
        return 'sync.log';
    }
}
HEREDOC
);

// Define a custom Automator class via eval() so it does not interfere with the IDE
eval(<<<HEREDOC
namespace Contao;

class Automator
{
    public function checkForUpdates() {}
    public function purgeSearchTables() {}
    public function purgeUndoTable() {}
    public function purgeVersionTable() {}
    public function purgeSystemLog() {}
    public function purgeImageCache() {}
    public function purgeScriptCache() {}
    public function purgePageCache() {}
    public function purgeSearchCache() {}
    public function purgeInternalCache() {}
    public function purgeTempFolder() {}
    public function generateXmlFiles() {}
    public function purgeXmlFiles() {}
    public function generateSitemap() {}
    public function rotateLogs() {}
    public function generateSymlinks() {}
    public function generateInternalCache() {}
    public function generateConfigCache() {}
    public function generateDcaCache() {}
    public function generateLanguageCache() {}
    public function generateDcaExtracts() {}
    public function generatePackageCache() {}

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
