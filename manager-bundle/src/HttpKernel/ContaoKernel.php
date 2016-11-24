<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\ManagerBundle\ContaoManager\Bundle\BundleLoader;
use Contao\ManagerBundle\ContaoManager\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\DelegatingParser;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\IniParser;
use Contao\ManagerBundle\ContaoManager\Bundle\Parser\JsonParser;
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
            $this->rootDir = dirname(dirname(dirname(dirname(dirname(__DIR__))))) . '/app';
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
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return dirname($this->getRootDir()).'/var/cache/'.$this->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return dirname($this->getRootDir()).'/var/logs';
    }

    /**
     * Loads Contao Manager plugins from Composer's installed.json
     *
     * @param string $installedJson
     */
    public function loadPlugins($installedJson)
    {
        if (file_exists($this->getRootDir() . '/ContaoManagerPlugin.php')) {
            include_once $this->getRootDir() . '/ContaoManagerPlugin.php';
        }

        $this->pluginLoader = new PluginLoader($installedJson);
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/../Resources/contao-manager/parameters.yml');

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

        $parser = new DelegatingParser();
        $parser->addParser(new JsonParser());
        $parser->addParser(new IniParser(dirname($this->getRootDir()) . '/system/modules'));

        $bundleLoader = new BundleLoader($this->pluginLoader, new ConfigResolverFactory(), $parser);

        $configs = $bundleLoader->getBundleConfigs(
            'dev' === $this->getEnvironment(),
            $this->debug ? null : $this->getCacheDir() . '/bundles.map'
        );

        foreach ($configs as $config) {
            $bundles[$config->getName()] = $config->getBundleInstance($this);
        }

        // Autoload AppBundle for convenience
        $appBundle = 'AppBundle\AppBundle';
        if (isset($bundles[$appBundle]) && class_exists($appBundle)) {
            $bundles[$appBundle] = new $appBundle();
        }
    }
}
