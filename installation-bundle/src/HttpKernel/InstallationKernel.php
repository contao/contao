<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\HttpKernel;

use Contao\ClassLoader;
use Contao\Config;
use Contao\InstallationBundle\ClassLoader\LibraryLoader;
use Contao\InstallationBundle\Controller\InstallationController;
use Contao\InstallationBundle\DependencyInjection\ContainerFactory;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a special installation kernel.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallationKernel extends \AppKernel
{
    /**
     * @var Request
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->initializeBundles();
        $this->bootHelperSystem();
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionClass(get_parent_class($this));
            $this->rootDir = dirname($r->getFileName());
        }

        return $this->rootDir;
    }

    /**
     * Checks if the real system can be booted.
     *
     * @return bool
     */
    public function canBootRealSystem()
    {
        return file_exists($this->getRootDir().'/config/parameters.yml')
            && file_exists($this->getRootDir().'/../system/config/localconfig.php')
            && file_exists($this->getRootDir().'/../var/bootstrap.php.cache')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $this->request = $request;

        if ($this->canBootRealSystem()) {
            return new RedirectResponse($this->getInstallToolUrl());
        }

        $this->boot();

        $controller = new InstallationController();
        $controller->setContainer($this->getContainer());
        $controller->runPostInstallCommands();

        return $controller->installAction();
    }

    /**
     * Boots the helper system.
     */
    private function bootHelperSystem()
    {
        $contaoDir = $this->getRootDir().'/../vendor/contao/core-bundle';

        require_once $contaoDir.'/src/Resources/contao/config/constants.php';
        require_once $contaoDir.'/src/Resources/contao/helper/functions.php';

        // Register the class loader
        $libraryLoader = new LibraryLoader($this->getRootDir());
        $libraryLoader->register();

        Config::preload();

        // Create the container
        $this->container = ContainerFactory::create($this, $this->request);
        System::setContainer($this->container);

        ClassLoader::scanAndRegister();
    }

    /**
     * Returns the install tool URL.
     *
     * @return string
     */
    private function getInstallToolUrl()
    {
        $routes = new RouteCollection();
        $routes->add('contao_install', new Route('/contao/install'));

        $context = new RequestContext();
        $context->fromRequest($this->request);
        $context->setBaseUrl('');

        return str_replace('/install.php/', '/', (new UrlGenerator($routes, $context))->generate('contao_install'));
    }
}
