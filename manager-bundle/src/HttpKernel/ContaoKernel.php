<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\ManagerBundle\ContaoManager\Bundle\BundleAutoloader;
use Contao\ManagerBundle\ContaoManager\PluginLoader;
use Contao\ManagerBundle\ContaoManagerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class ContaoKernel extends Kernel
{
    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            new ContaoManagerBundle()
        ];

        $this->addBundlesFromPlugins($bundles);

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $this->rootDir = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/system';
        }

        return $this->rootDir;
    }

    /**
     * Sets the application root dir.
     *
     * @param string $dir
     */
    public function setRootDir($dir)
    {
        $this->rootDir = realpath($dir) ?: null;
    }

    /**
     * Loads Contao Manager plugins from Composer's installed.json
     *
     * @param string $installedJson
     */
    public function loadPlugins($installedJson)
    {
        $this->pluginLoader = new PluginLoader($installedJson);
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/../Resources/config/parameters.yml');

        if (file_exists($this->getRootDir() . '/config/parameters.yml')) {
            $loader->load($this->getRootDir() . '/config/parameters.yml');
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareContainer(ContainerBuilder $container)
    {
        // Set plugin loader so it's available in ContainerBuilder
        if ($this->pluginLoader) {
            $container->set('contao_manager.plugin_loader', $this->pluginLoader);
            $container->setParameter('contao_manager.plugins', $this->pluginLoader->getClasses());
        }

        parent::prepareContainer($container);
    }

    /**
     * @inheritdoc
     */
    protected function initializeContainer()
    {
        parent::initializeContainer();

        // Set plugin loader again so it's available at runtime (synthetic service)
        if ($this->pluginLoader) {
            $this->container->set('contao_manager.plugin_loader', $this->pluginLoader);
        }
    }

    /**
     * Adds bundles from plugins to the given array.
     *
     * @param array $bundles
     */
    private function addBundlesFromPlugins(&$bundles)
    {
        if (!$this->pluginLoader instanceof PluginLoader) {
            return;
        }

        $autoloader = new BundleAutoloader(
            $this->pluginLoader,
            $this->getRootDir() . '/modules'
        );

        $configs = $autoloader->getBundleConfigs(
            'dev' === $this->getEnvironment(),
            $this->debug ? null : $this->getCacheDir() . '/bundles.map'
        );

        foreach ($configs as $config) {
            $bundles[] = $config->getBundleInstance($this);
        }
    }
}
