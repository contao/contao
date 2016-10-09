<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\ManagerBundle\ContaoManager\PluginLoader;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerBundle\Manager\Bundle\BundleAutoloader;
use Contao\ManagerBundle\Manager\Bundle\ConfigInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class ContaoKernel extends Kernel
{
    /**
     * @var array
     */
    protected $bundleConfigs = [];

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

        $this->addManagedBundles($bundles);

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
     * Adds the managed bundles
     *
     * @param array $bundles
     */
    private function addManagedBundles(&$bundles)
    {
        $this->loadBundleCache();

        if (!is_array($this->bundleConfigs) || 0 === count($this->bundleConfigs)) {
            $this->bundleConfigs = $this->loadBundleConfigs();
            $this->writeBundleCache();
        }

        foreach ($this->bundleConfigs as $config) {
            $bundles[] = $config->getBundleInstance($this);
        }
    }

    /**
     * Writes the bundle cache
     */
    private function writeBundleCache()
    {
        if ($this->debug) {
            return;
        }

        if (!@mkdir($this->getCacheDir(), 0777, true) && !is_dir($this->getCacheDir())) {
            throw new \RuntimeException('Could not create cache dir at ' . $this->getCacheDir());
        }

        file_put_contents(
            $this->getCacheDir() . '/bundles.map',
            serialize($this->bundleConfigs)
        );
    }

    /**
     * Loads the bundle cache
     */
    private function loadBundleCache()
    {
        if ($this->debug || !is_file($this->getCacheDir() . '/bundles.map')) {
            return;
        }

        $this->bundleConfigs = unserialize(file_get_contents($this->getCacheDir() . '/bundles.map'));
    }

    /**
     * Generates the bundles map
     *
     * @return ConfigInterface[]
     */
    private function loadBundleConfigs()
    {
        if (null === $this->pluginLoader) {
            return [];
        }

        $rootDir = $this->getRootDir();
        $autoloader = new BundleAutoloader(
            $this->pluginLoader->getInstances(),
            $rootDir . '/modules'
        );

        return $autoloader->load('dev' === $this->getEnvironment());
    }
}
