<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Autoload;

/**
 * Autoload configuration interface.
 *
 * @author Leo Feyer <https://contao.org>
 */
interface ConfigInterface
{
    /**
     * Returns a new configuration object.
     *
     * @return static The configuration object
     */
    public static function create();

    /**
     * Returns the class name of the bundle.
     *
     * @return string The class name
     */
    public function getClass();

    /**
     * Sets the class name of the bundle.
     *
     * @param string $class The class name
     *
     * @return $this The object instance
     */
    public function setClass($class);

    /**
     * Returns the bundle name.
     *
     * @return string The bundle name
     */
    public function getName();

    /**
     * Sets the bundle name.
     *
     * @param string $name The bundle name
     *
     * @return $this The object instance
     */
    public function setName($name);

    /**
     * Returns the replaces array.
     *
     * @return array The replaces array
     */
    public function getReplace();

    /**
     * Sets the replaces array.
     *
     * @param array $replace The replaces array
     *
     * @return $this The object instance
     */
    public function setReplace(array $replace);

    /**
     * Returns the environments array.
     *
     * @return array The environments array
     */
    public function getEnvironments();

    /**
     * Sets the environments array.
     *
     * @param array $environments The environments array
     *
     * @return $this The object instance
     */
    public function setEnvironments(array $environments);

    /**
     * Returns the "load after" array.
     *
     * @return array The "load after" array
     */
    public function getLoadAfter();

    /**
     * Sets the "load after" array.
     *
     * @param array $loadAfter The "load after" array
     *
     * @return $this The object instance
     */
    public function setLoadAfter(array $loadAfter);
}
