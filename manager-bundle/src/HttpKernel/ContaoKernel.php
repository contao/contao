<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\IniParser;
use Contao\ManagerPlugin\Bundle\Parser\JsonParser;
use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
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
        $bundles = [];

        $this->addBundlesFromPlugins($bundles);

        return $bundles;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $this->rootDir = dirname(dirname(dirname(dirname(dirname(__DIR__))))).'/app';
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
     * Gets the class to load Contao Manager plugins.
     *
     * @return PluginLoader
     */
    public function getPluginLoader()
    {
        if (null === $this->pluginLoader) {
            $this->pluginLoader = new PluginLoader($this->getRootDir().'/../vendor/composer/installed.json');
        }

        return $this->pluginLoader;
    }

    /**
     * Sets the class to load Contao Manager plugins.
     *
     * @param PluginLoader $pluginLoader
     */
    public function setPluginLoader(PluginLoader $pluginLoader)
    {
        $this->pluginLoader = $pluginLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (file_exists($this->getRootDir().'/config/parameters.yml')) {
            $loader->load($this->getRootDir().'/config/parameters.yml');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareContainer(ContainerBuilder $container)
    {
        // Set plugin loader so it's available in ContainerBuilder
        $container->set('contao_manager.plugin_loader', $this->getPluginLoader());

        parent::prepareContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContainer()
    {
        parent::initializeContainer();

        // Set plugin loader again so it's available at runtime (synthetic service)
        $this->getContainer()->set('contao_manager.plugin_loader', $this->getPluginLoader());
    }

    /**
     * Adds bundles from plugins to the given array.
     *
     * @param array $bundles
     */
    private function addBundlesFromPlugins(&$bundles)
    {
        $parser = new DelegatingParser();
        $parser->addParser(new JsonParser());
        $parser->addParser(new IniParser(dirname($this->getRootDir()).'/system/modules'));

        $bundleLoader = new BundleLoader($this->getPluginLoader(), new ConfigResolverFactory(), $parser);

        $configs = $bundleLoader->getBundleConfigs(
            'dev' === $this->getEnvironment(),
            $this->debug ? null : $this->getCacheDir().'/bundles.map'
        );

        foreach ($configs as $config) {
            $bundles[$config->getName()] = $config->getBundleInstance($this);
        }

        // Autoload AppBundle for convenience
        $appBundle = 'AppBundle\AppBundle';

        if (!isset($bundles[$appBundle]) && class_exists($appBundle)) {
            $bundles[$appBundle] = new $appBundle();
        }
    }
}
