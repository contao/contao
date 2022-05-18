<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Composer\Autoload\ClassLoader;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;

// Suppress error messages (see #1422)
@ini_set('display_errors', '0');

// Disable the phar stream wrapper for security reasons (see #105)
if (in_array('phar', stream_get_wrappers(), true)) {
    stream_wrapper_unregister('phar');
}

// System maintenance mode comes first as it has to work even if the vendor directory does not exist
if (file_exists(__DIR__.'/../var/maintenance.html')) {
    $contents = file_get_contents(__DIR__.'/../var/maintenance.html');

    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Length: '.strlen((string) $contents));
    header('Cache-Control: no-store');

    die($contents);
}

/** @var ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

$request = Request::createFromGlobals();
$kernel = ContaoKernel::fromRequest(dirname(__DIR__), $request);

$response = $kernel->handle($request);
$response->send();

if ($kernel instanceof TerminableInterface) {
    $kernel->terminate($request, $response);
}
