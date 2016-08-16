<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\Manager\Bundle;

/**
 * Finds the autoload bundles
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class BundleAutoloader
{
    /**
     * @var string
     */
    private $installedJson;

    /**
     * @var string
     */
    protected $modulesDir;

    /**
     * Constructor
     *
     * @param string $installedJson
     * @param string $modulesDir
     *
     * @throws \InvalidArgumentException If the installed.json does not exist
     */
    public function __construct($installedJson, $modulesDir)
    {
        $this->modulesDir = $modulesDir;
        $this->installedJson = $installedJson;

        if (!is_file($installedJson)) {
            throw new \InvalidArgumentException(
                sprintf('Composer installed.json was not found at "%s"', $installedJson)
            );
        }
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

        foreach ($this->getManagerPlugins() as $plugin) {
            if ($plugin instanceof BundlePluginInterface) {
                foreach ($plugin->getAutoloadConfigs($jsonParser, $iniParser) as $config) {
                    $resolver->add($config);
                }
            }
        }

        return $resolver->getBundleConfigs($development);
    }

    /**
     * Get all instances of manager plugins from Composer's installed.json
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    private function getManagerPlugins()
    {
        $plugins = [];
        $json = json_decode(file_get_contents($this->installedJson), true);

        if (null === $json) {
            throw new \RuntimeException(sprintf('File "%s" cannot be decoded', $this->installedJson));
        }

        foreach ($json as $package) {
            if (isset($package['extra']['contao-manager-plugin'])) {
                $plugins[] = new $package['extra']['contao-manager-plugin'];
            }
        }

        return $plugins;
    }
}
