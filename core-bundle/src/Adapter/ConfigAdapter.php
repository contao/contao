<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Adapter;

use Contao\Config;

/**
 * Provides an adapter for the Contao Config class.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 * @internal
 *
 * @deprecated Deprecated since Contao 4.1, to be removed in Contao 5.
 *             Use Contao\CoreBundle\Framework\ContaoFrameworkInterface::getAdapter('Config').
 */
class ConfigAdapter
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Initializes the Config class.
     */
    public function initialize()
    {
        $this->config = Config::getInstance();
    }

    /**
     * Saves the local configuration file.
     */
    public function save()
    {
        $this->config->save();
    }

    /**
     * Returns true if the installation is complete.
     *
     * @return bool True if the installation is complete
     */
    public function isComplete()
    {
        return $this->config->isComplete();
    }

    /**
     * Adds a configuration variable to the local configuration file.
     *
     * @param string $key   The full variable name
     * @param mixed  $value The configuration value
     */
    public function add($key, $value)
    {
        $this->config->add($key, $value);
    }

    /**
     * Alias for Config::add().
     *
     * @param string $key   The full variable name
     * @param mixed  $value The configuration value
     */
    public function update($key, $value)
    {
        $this->config->update($key, $value);
    }

    /**
     * Removes a configuration variable.
     *
     * @param string $key The full variable name
     */
    public function delete($key)
    {
        $this->config->delete($key);
    }

    /**
     * Checks whether a configuration value exists.
     *
     * @param string $key The short key
     *
     * @return bool True if the configuration value exists
     */
    public function has($key)
    {
        return Config::has($key);
    }

    /**
     * Returns a configuration value.
     *
     * @param string $key The short key
     *
     * @return mixed|null The configuration value
     */
    public function get($key)
    {
        return Config::get($key);
    }

    /**
     * Temporarily sets a configuration value.
     *
     * @param string $key   The short key
     * @param string $value The configuration value
     */
    public function set($key, $value)
    {
        Config::set($key, $value);
    }

    /**
     * Permanently sets a configuration value.
     *
     * @param string $key   The short key or full variable name
     * @param mixed  $value The configuration value
     */
    public function persist($key, $value)
    {
        Config::persist($key, $value);
    }

    /**
     * Permanently removes a configuration value.
     *
     * @param string $key The short key or full variable name
     */
    public function remove($key)
    {
        Config::remove($key);
    }

    /**
     * Preloads the default and local configuration.
     */
    public function preload()
    {
        Config::preload();
    }
}
