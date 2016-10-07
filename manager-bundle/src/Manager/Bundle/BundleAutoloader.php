<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Manager\Bundle;

use Contao\ManagerBundle\ContaoManager\PluginLoader;

/**
 * Finds the autoload bundles
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BundleAutoloader
{
    /**
     * @var array
     */
    private $plugins;

    /**
     * @var string
     */
    protected $modulesDir;

    /**
     * Constructor
     *
     * @param array $plugins
     * @param string $modulesDir
     *
     * @throws \InvalidArgumentException If the installed.json does not exist
     */
    public function __construct($plugins, $modulesDir)
    {
        $this->modulesDir = $modulesDir;
        $this->plugins = $plugins;
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

        foreach ($this->plugins as $plugin) {
            if ($plugin instanceof BundlePluginInterface) {
                foreach ($plugin->getAutoloadConfigs($jsonParser, $iniParser) as $config) {
                    $resolver->add($config);
                }
            }
        }

        return $resolver->getBundleConfigs($development);
    }
}
