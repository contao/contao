<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\HttpKernel;

use Contao\ManagerPlugin\Bundle\BundleLoader;
use Contao\ManagerPlugin\Bundle\Config\ConfigResolverFactory;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\ManagerPlugin\Bundle\Parser\IniParser;
use Contao\ManagerPlugin\Bundle\Parser\JsonParser;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder as PluginContainerBuilder;
use Contao\ManagerPlugin\PluginLoader;
use Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class ContaoKernel extends Kernel
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var BundleLoader
     */
    private $bundleLoader;

    /**
     * Constructor.
     *
     * @param string $projectDir
     * @param string $environment
     * @param bool   $debug
     */
    public function __construct($projectDir, $environment, $debug)
    {
        $this->projectDir = $projectDir;

        parent::__construct($environment, $debug);
    }

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
    public function getProjectDir()
    {
        return $this->projectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $this->rootDir = $this->projectDir.'/app';
        }

        return $this->rootDir;
    }

    /**
     * Sets the application root dir.
     *
     * @param string $dir
     *
     * @deprecated Deprecated since Contao 4.4, to be removed in Contao 5.0
     */
    public function setRootDir($dir)
    {
        @trigger_error(
            'Using ContaoKernel::setRootDir() has been deprecated and will no longer work in Contao 5.0.',
            E_USER_DEPRECATED
        );

        $this->rootDir = realpath($dir) ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->projectDir.'/var/cache/'.$this->getEnvironment();
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->projectDir.'/var/logs';
    }

    /**
     * Gets the class to load Contao Manager plugins.
     *
     * @return PluginLoader
     */
    public function getPluginLoader()
    {
        if (null === $this->pluginLoader) {
            $this->pluginLoader = new PluginLoader($this->projectDir.'/vendor/composer/installed.json');
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
     * Gets the bundle loader.
     *
     * @return BundleLoader
     */
    public function getBundleLoader()
    {
        if (null === $this->bundleLoader) {
            $parser = new DelegatingParser();
            $parser->addParser(new JsonParser());
            $parser->addParser(new IniParser($this->projectDir.'/system/modules'));

            $this->bundleLoader = new BundleLoader($this->getPluginLoader(), new ConfigResolverFactory(), $parser);
        }

        return $this->bundleLoader;
    }

    /**
     * Sets the bundle loader.
     *
     * @param BundleLoader $bundleLoader
     */
    public function setBundleLoader(BundleLoader $bundleLoader)
    {
        $this->bundleLoader = $bundleLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if (file_exists($this->getRootDir().'/config/parameters.yml')) {
            $loader->load($this->getRootDir().'/config/parameters.yml');
        }

        /** @var ConfigPluginInterface[] $plugins */
        $plugins = $this->getPluginLoader()->getInstancesOf(PluginLoader::CONFIG_PLUGINS);

        foreach ($plugins as $plugin) {
            $plugin->registerContainerConfiguration($loader, []);
        }

        if (file_exists($this->getRootDir().'/config/parameters.yml')) {
            $loader->load($this->getRootDir().'/config/parameters.yml');
        }

        $loader->load(function (ContainerBuilder $container) use ($loader) {
            $environment = $container->getParameter('kernel.environment');

            if (file_exists($this->getRootDir().'/config/config_'.$environment.'.yml')) {
                $loader->load($this->getRootDir().'/config/config_'.$environment.'.yml');
            } elseif (file_exists($this->getRootDir().'/config/config.yml')) {
                $loader->load($this->getRootDir().'/config/config.yml');
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function getContainerBuilder()
    {
        $container = new PluginContainerBuilder($this->getPluginLoader(), []);
        $container->getParameterBag()->add($this->getKernelParameters());

        if (class_exists('ProxyManager\Configuration') && class_exists('Symfony\Bridge\ProxyManager\LazyProxy\Instantiator\RuntimeInstantiator')) {
            $container->setProxyInstantiator(new RuntimeInstantiator());
        }

        return $container;
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
        $configs = $this->getBundleLoader()->getBundleConfigs(
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
