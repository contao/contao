<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Config;

/**
 * Provides an adapter for the legacy Contao Config class so it can be injected
 * and unit tested.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 */
class ConfigAdapter
{
    /**
     * @var \Config
     */
    private $config;

    /**
     * Save the local configuration file
     */
    public function save()
    {
        $this->config->save();
    }

    /**
     * Return true if the installation is complete.
     *
     * @return boolean
     */
    public function isComplete()
    {
        return $this->config->isComplete();
    }

    /**
     * Return all active modules as array
     *
     * @return array An array of active modules
     *
     * @deprecated Use ModuleLoader::getActive() instead
     */
    public function getActiveModules()
    {
        return $this->config->getActiveModules();
    }

    /**
     * Add a configuration variable to the local configuration file
     *
     * @param string $strKey The full variable name
     * @param mixed  $varValue The configuration value
     */
    public function add($strKey, $varValue)
    {
        $this->config->add($strKey, $varValue);
    }

    /**
     * Alias for Config::add()
     *
     * @param string $strKey The full variable name
     * @param mixed  $varValue The configuration value
     */
    public function update($strKey, $varValue)
    {
        $this->config->update($strKey, $varValue);
    }

    /**
     * Remove a configuration variable
     *
     * @param string $strKey The full variable name
     */
    public function delete($strKey)
    {
        $this->config->delete($strKey);
    }

    /**
     * Return a configuration value
     *
     * @param string $strKey The short key (e.g. "displayErrors")
     *
     * @return mixed|null The configuration value
     */
    public function get($strKey)
    {
        return \Config::get($strKey);
    }

    /**
     * Temporarily set a configuration value
     *
     * @param string $strKey The short key (e.g. "displayErrors")
     * @param string $varValue The configuration value
     */
    public function set($strKey, $varValue)
    {
        \Config::set($strKey, $varValue);
    }

    /**
     * Permanently set a configuration value
     *
     * @param string $strKey   The short key or full variable name
     * @param mixed  $varValue The configuration value
     */
    public function persist($strKey, $varValue)
    {
        \Config::persist($strKey, $varValue);
    }

    /**
     * Permanently remove a configuration value
     *
     * @param string $strKey The short key or full variable name
     */
    public function remove($strKey)
    {
        \Config::remove($strKey);
    }

    /**
     * Preload the default and local configuration
     */
    public function preload()
    {
        \Config::preload();
    }

    /**
     * Instantiates the legacy Config instance if not already instantiated.
     */
    public function instantiate()
    {
        $this->config = \Config::getInstance();
    }
}
