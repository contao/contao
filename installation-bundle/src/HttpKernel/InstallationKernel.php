<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\InstallationBundle\HttpKernel;

use Contao\ClassLoader;
use Contao\Config;
use Contao\InstallationBundle\ClassLoader\LibraryLoader;
use Contao\InstallationBundle\DependencyInjection\ContainerFactory;
use Contao\InstallationBundle\Translation\LanguageResolver;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a special installation kernel.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class InstallationKernel extends \AppKernel
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if (file_exists($this->getRootDir() . '/config/parameters.yml')) {
            parent::boot();
            $this->bootRealSystem();
        } else {
            $this->initializeBundles();
            $this->bootHelperSystem();
        }
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
     * Boots the real system.
     */
    private function bootRealSystem()
    {
        $request = Request::createFromGlobals();

        $request->attributes->add([
            '_route' => 'contao_install',
            '_route_params' => [
                '_scope' => 'backend'
            ],
        ]);

        $container = $this->getContainer();

        $requestStack = $container->get('request_stack');
        $requestStack->push($request);

        $resolver = new LanguageResolver($requestStack, __DIR__ . '/../Resources/translations');

        $container->get('translator')->setLocale($resolver->getLocale());
        $container->get('contao.framework')->setSkipTokenCheck(true)->initialize();
    }

    /**
     * Boots the helper system.
     */
    private function bootHelperSystem()
    {
        $contaoDir = $this->getRootDir() . '/../vendor/contao/core-bundle';

        require_once $contaoDir . '/src/Resources/contao/config/constants.php';
        require_once $contaoDir . '/src/Resources/contao/helper/functions.php';

        // Register the class loader
        $libraryLoader = new LibraryLoader($this->getRootDir());
        $libraryLoader->register();

        Config::preload();

        // Create the container
        $this->container = ContainerFactory::create($this);
        System::setContainer($this->container);

        ClassLoader::scanAndRegister();
    }
}
