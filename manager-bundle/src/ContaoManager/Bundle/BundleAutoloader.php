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
     * Constructor
     *
     * @param PluginLoader $pluginLoader
     * @param string $modulesDir
     *
     * @throws \InvalidArgumentException If the installed.json does not exist
     */
    public function __construct(PluginLoader $pluginLoader, $modulesDir)
    {
        $this->modulesDir = $modulesDir;
        $this->pluginLoader = $pluginLoader;
    }

    /**
     * Returns an ordered bundle map
     *
     * @param bool $development
     *
     * @return array
     */
    public function load($development)
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

        return $resolver->getBundleConfigs($development);
    }
}
