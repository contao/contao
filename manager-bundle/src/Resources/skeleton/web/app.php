<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerBundle\ContaoManager\Plugin;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

// Suppress error messages (see #1422)
@ini_set('display_errors', '0');

// Disable the phar stream wrapper for security reasons (see #105)
if (\in_array('phar', stream_get_wrappers(), true)) {
    stream_wrapper_unregister('phar');
}

/** @var Composer\Autoload\ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';

if (file_exists(__DIR__.'/../.env')) {
    (new Dotenv())->load(__DIR__.'/../.env');
}

// See https://github.com/symfony/recipes/blob/master/symfony/framework-bundle/3.3/public/index.php#L27
if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts(explode(',', $trustedHosts));
}

unset($trustedProxies, $trustedHosts);

Plugin::autoloadModules(__DIR__.'/../system/modules');
ContaoKernel::setProjectDir(\dirname(__DIR__));

$kernel = new ContaoKernel('prod', false);

// Enable the Symfony reverse proxy
if (!($_SERVER['DISABLE_HTTP_CACHE'] ?? false)) {
    $kernel = $kernel->getHttpCache();
}

Request::enableHttpMethodParameterOverride();

// Handle the request
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
