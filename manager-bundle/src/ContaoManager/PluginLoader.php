<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\ManagerBundle\ContaoManager;

/**
 * Finds Contao manager plugins from Composer's installed.json
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 */
class PluginLoader
{
    const BUNDLE_PLUGINS = 'Contao\ManagerBundle\ContaoManager\Bundle\BundlePluginInterface';
    const CONFIG_PLUGINS = 'Contao\ManagerBundle\ContaoManager\Config\ConfigPluginInterface';
    const ROUTING_PLUGINS = 'Contao\ManagerBundle\ContaoManager\Routing\RoutingPluginInterface';

    /**
     * @var string
     */
    private $installedJson;

    /**
     * @var array
     */
    private $plugins;

    /**
     * Constructor.
     *
     * @param string $installedJson
     */
    public function __construct($installedJson)
    {
        $this->installedJson = $installedJson;
    }

    /**
     * Gets instances of manager plugins.
     *
     * @return array
     */
    public function getInstances()
    {
        $this->load();

        return $this->plugins;
    }

    /**
     * Gets instances of manager plugins of given type (see class constants).
     *
     * @param string $type
     * @param bool   $reverseOrder
     *
     * @return array
     */
    public function getInstancesOf($type, $reverseOrder = false)
    {
        $plugins = array_filter(
            $this->getInstances(),
            function ($plugin) use ($type) {
                return is_a($plugin, $type);
            }
        );

        return $reverseOrder ? array_reverse($plugins) : $plugins;
    }

    /**
     * Loads plugins from Composer's installed.json
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     *
     * @todo implement loading order
     */
    private function load()
    {
        if (null !== $this->plugins) {
            return;
        }

        if (!is_file($this->installedJson)) {
            throw new \InvalidArgumentException(
                sprintf('Composer installed.json was not found at "%s"', $this->installedJson)
            );
        }

        $json = json_decode(file_get_contents($this->installedJson), true);

        if (null === $json) {
            throw new \RuntimeException(sprintf('File "%s" cannot be decoded', $this->installedJson));
        }

        foreach ($json as $package) {
            if (isset($package['extra']['contao-manager-plugin'])) {
                $this->plugins[$package['name']] = new $package['extra']['contao-manager-plugin'];
            }
        }
    }
}
