<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\ManagerBundle\ContaoManager\Plugin as ManagerBundlePlugin;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

// Disable the phar stream wrapper for security reasons (see #105)
if (\in_array('phar', stream_get_wrappers(), true)) {
    stream_wrapper_unregister('phar');
}

/** @var Composer\Autoload\ClassLoader */
$loader = require __DIR__.'/../vendor/autoload.php';

/***********************************************************************************************/
/*                               ###  READ FIRST  ###                                          */
/* Access to debug front controllers must only be allowed on localhost or with authentication. */
/* Use the "contao:install-web-dir" console command to set a password for the dev entry point. */
/***********************************************************************************************/

if (file_exists(__DIR__.'/../.env')) {
    (new Dotenv())->load(__DIR__.'/../.env');
}

$request = Request::createFromGlobals();
$accessKey = @getenv('APP_DEV_ACCESSKEY', true);

if (
    $request->server->has('HTTP_CLIENT_IP')
    || $request->server->has('HTTP_X_FORWARDED_FOR')
    || !(IpUtils::checkIp($request->getClientIp(), ['127.0.0.1', 'fe80::1', '::1']) || PHP_SAPI === 'cli-server')
) {
    if (false === $accessKey) {
        header('HTTP/1.0 403 Forbidden');
        die(sprintf('You are not allowed to access this file. Check %s for more information.', basename(__FILE__)));
    }

    if (
        null === $request->getUser()
        || !password_verify($request->getUser().':'.$request->getPassword(), $accessKey)
    ) {
        header('WWW-Authenticate: Basic realm="Contao debug"');
        header('HTTP/1.0 401 Unauthorized');
        die(sprintf('You are not allowed to access this file. Check %s for more information.', basename(__FILE__)));
    }
}

unset($accessKey);

Debug::enable();
Request::enableHttpMethodParameterOverride();
ManagerBundlePlugin::autoloadModules(__DIR__.'/../system/modules');
ContaoKernel::setProjectDir(dirname(__DIR__));

// Handle the request
$kernel = new ContaoKernel('dev', true);
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
