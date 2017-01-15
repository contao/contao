<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Contao\InstallationBundle\HttpKernel\InstallationKernel;
use Symfony\Component\HttpFoundation\Request;

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);

/** @var Composer\Autoload\ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';

class AppKernel extends \Contao\ManagerBundle\HttpKernel\ContaoKernel
{
}

$kernel = new InstallationKernel('prod', false);
$kernel->setRootDir(dirname(__DIR__).'/app');

// Handle the request
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
