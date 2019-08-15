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
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;

// Suppress error messages (see #1422)
@ini_set('display_errors', '0');

// Disable the phar stream wrapper for security reasons (see #105)
if (\in_array('phar', stream_get_wrappers(), true)) {
    stream_wrapper_unregister('phar');
}

/** @var Composer\Autoload\ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';

$request = Request::createFromGlobals();
$kernel = ContaoKernel::fromRequest(\dirname(__DIR__), $request);
$response = $kernel->handle($request);

// Force no-cache on all responses in the preview front controller
$response->headers->set('Cache-Control', 'no-store');

// Strip all tag headers from the response
$response->headers->remove(TagHeaderFormatter::DEFAULT_HEADER_NAME);
$response->send();

if ($kernel instanceof TerminableInterface) {
    $kernel->terminate($request, $response);
}
