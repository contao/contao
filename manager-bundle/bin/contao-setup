#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerBundle\Console\ContaoApplication;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Filesystem\Filesystem;

error_reporting(-1);
set_time_limit(0);
@ini_set('display_startup_errors', '1');
@ini_set('display_errors', '1');
@ini_set('zlib.output_compression', '0');

if (file_exists(__DIR__.'/../autoload.php')) {
    $projectDir = \dirname(__DIR__, 2);
} elseif (file_exists(__DIR__.'/../../../../autoload.php')) {
    $projectDir = \dirname(__DIR__, 5);
} elseif (false !== ($cwd = getcwd()) && file_exists($cwd.'/vendor/autoload.php')) {
    $projectDir = $cwd;
} else {
    $projectDir = \dirname(__DIR__, 4);
}

require $projectDir.'/vendor/autoload.php';

$kernel = ContaoKernel::fromInput($projectDir, new ArrayInput(['--env' => 'prod']));

// Purge the cache in case it is corrupted
(new Filesystem())->remove($kernel->getCacheDir());

$application = new ContaoApplication($kernel);
$application->setDefaultCommand('contao:setup');
$application->run();
