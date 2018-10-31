<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerBundle\HttpKernel\JwtBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;

// Suppress error messages (see #1422)
@ini_set('display_errors', '0');

/** @var Composer\Autoload\ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';

// Handle the request
$kernel = ContaoKernel::create(\dirname(__DIR__), JwtBag::create(\dirname(__DIR__), $_COOKIE));
$request = Request::createFromGlobals();
$response = $kernel->getHttpKernel()->handle($request);
$response->send();

if ($kernel instanceof TerminableInterface) {
    $kernel->terminate($request, $response);
}
