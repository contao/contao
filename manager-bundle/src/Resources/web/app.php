<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

use Contao\ManagerBundle\HttpKernel\ContaoCache;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @var Composer\Autoload\ClassLoader
 */
$loader = require __DIR__.'/{vendor-dir}/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

$kernel = new ContaoKernel('prod', false);
$kernel->setRootDir(__DIR__ . '/{root-dir}');
$kernel->loadPlugins(__DIR__ . '/{vendor-dir}/composer/installed.json');
$kernel->loadClassCache();

// Enable the Symfony reverse proxy
$kernel = new ContaoCache($kernel);

// Handle the request
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
