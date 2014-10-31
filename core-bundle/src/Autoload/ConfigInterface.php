<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\CoreBundle\Autoload;

/**
 * Autoload configuration interface
 *
 * @author Leo Feyer <https://contao.org>
 */
interface ConfigInterface
{
    /**
     * Returns a new configuration object
     *
     * @return static The configuration object
     */
    public static function create();

    /**
     * Returns the class
     *
     * @return string The class
     */
    public function getClass();

    /**
     * Sets the class name
     *
     * @param string $class The class name
     *
     * @return $this The object instance
     */
    public function setClass($class);

    /**
     * Returns the name
     *
     * @return string The name
     */
    public function getName();

    /**
     * Sets the bundle name
     *
     * @param string $name The bundle name
     *
     * @return $this The object instance
     */
    public function setName($name);

    /**
     * Returns the replaces
     *
     * @return array The replace
     */
    public function getReplace();

    /**
     * Sets the replaces
     *
     * @param array $replace The replaces
     *
     * @return $this The object instance
     */
    public function setReplace(array $replace);

    /**
     * Returns the environments
     *
     * @return array The environments
     */
    public function getEnvironments();

    /**
     * Sets the environments
     *
     * @param array $environments The environments
     *
     * @return $this The object instance
     */
    public function setEnvironments(array $environments);

    /**
     * Returns the "load after" bundles
     *
     * @return array The "load after" bundles
     */
    public function getLoadAfter();

    /**
     * Sets the "load after" bundles
     *
     * @param array $loadAfter The "load after" bundles
     *
     * @return $this The object instance
     */
    public function setLoadAfter(array $loadAfter);
}
