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
use FOS\HttpCache\TagHeaderFormatter\TagHeaderFormatter;
use Symfony\Component\Debug\Debug;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;

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

$request = Request::createFromGlobals();

if (
    $request->server->has('HTTP_CLIENT_IP')
    || $request->server->has('HTTP_X_FORWARDED_FOR')
    || !(IpUtils::checkIp($request->getClientIp(), ['127.0.0.1', 'fe80::1', '::1']) || PHP_SAPI === 'cli-server')
) {
    ##########################################################################
    #                                                                        #
    #  Access to debug front controllers is only allowed on localhost or     #
    #  with authentication. Use the "contao:install-web-dir -p" command to   #
    #  set a password for the dev entry point.                               #
    #                                                                        #
    ##########################################################################
    $accessKey = @getenv('APP_DEV_ACCESSKEY', true);

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

    unset($accessKey);
}

Debug::enable();
Request::enableHttpMethodParameterOverride();
Plugin::autoloadModules(__DIR__.'/../system/modules');
ContaoKernel::setProjectDir(\dirname(__DIR__));

// Handle the request
$kernel = new ContaoKernel('dev', true);
$response = $kernel->handle($request);

$response->send();
$kernel->terminate($request, $response);
