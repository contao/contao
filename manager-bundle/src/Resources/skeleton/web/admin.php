<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\CoreBundle\Exception\ResponseException;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\ManagerBundle\HttpKernel\JwtManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\TerminableInterface;

// Suppress error messages (see #1422)
@ini_set('display_errors', '0');

/** @var Composer\Autoload\ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';

$request = Request::createFromGlobals();
$jwtManager = new JwtManager(\dirname(__DIR__));

try {
    $jwt = $jwtManager->parseRequest($request);
} catch (ResponseException $e) {
    $e->getResponse()->send();
    exit;
}

// Handle the request
$kernel = ContaoKernel::create(\dirname(__DIR__), $jwt['debug'] ?? false);
$response = $kernel->handle($request);

// Force no-cache on all responses in the preview front controller
$response->headers->set('Cache-Control', 'no-store');
$jwtManager->addResponseCookie($response, $jwt);

$response->send();

if ($kernel instanceof TerminableInterface) {
    $kernel->terminate($request, $response);
}
