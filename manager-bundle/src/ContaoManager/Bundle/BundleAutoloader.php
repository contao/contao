<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager\Bundle;

use Contao\ManagerBundle\ContaoManager\PluginLoader;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Finds the autoload bundles
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BundleAutoloader
{
    /**
     * @var PluginLoader
     */
    private $pluginLoader;

    /**
     * @var string
     */
    protected $modulesDir;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Constructor
     *
     * @param PluginLoader $pluginLoader
     * @param string       $modulesDir
     * @param Filesystem   $filesystem
     */
    public function __construct(PluginLoader $pluginLoader, $modulesDir, Filesystem $filesystem = null)
    {
        $this->modulesDir = $modulesDir;
        $this->pluginLoader = $pluginLoader;
        $this->filesystem = $filesystem;

        if (null === $this->filesystem) {
            $this->filesystem = new Filesystem();
        }
    }

    /**
     * Returns an ordered bundle map
     *
     * @param bool        $development
     * @param string|null $cacheFile
     *
     * @return ConfigInterface[]
     */
    public function getBundleConfigs($development, $cacheFile = null)
    {
        if (null !== $cacheFile) {
            return $this->loadFromCache($development, $cacheFile);
        }

        return $this->loadFromPlugins($development, $cacheFile);
    }

    /**
     * Loads the bundle cache
     *
     * @param bool        $development
     * @param string|null $cacheFile
     *
     * @return ConfigInterface[]
     */
    private function loadFromCache($development, $cacheFile)
    {
        $bundleConfigs = is_file($cacheFile) ? unserialize(file_get_contents($cacheFile)) : null;

        if (!is_array($bundleConfigs) || 0 === count($bundleConfigs)) {
            $bundleConfigs = $this->loadFromPlugins($development, $cacheFile);
        }

        return $bundleConfigs;
    }

    /**
     * Generates the bundles map
     *
     * @param bool        $development
     * @param string|null $cacheFile
     *
     * @return ConfigInterface[]
     */
    private function loadFromPlugins($development, $cacheFile)
    {
        $resolver = new ConfigResolver();
        $jsonParser = new JsonParser();
        $iniParser = new IniParser($this->modulesDir);

        /** @var BundlePluginInterface[] $plugins */
        $plugins = $this->pluginLoader->getInstancesOf(PluginLoader::BUNDLE_PLUGINS);

        foreach ($plugins as $plugin) {
            foreach ($plugin->getBundles($jsonParser, $iniParser) as $config) {
                $resolver->add($config);
            }
        }

        $bundleConfigs = $resolver->getBundleConfigs($development);

        if (null !== $cacheFile) {
            $this->filesystem->dumpFile($cacheFile, serialize($bundleConfigs));
        }

        return $bundleConfigs;
    }
}
