<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Adds composer packages version to the container.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddPackagesPass implements CompilerPassInterface
{
    private $configFile;

    /**
     * Constructor.
     *
     * @param string $configFile Path to the composer installed.json file
     */
    public function __construct($configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!is_file($this->configFile)) {
            $json = null;
        } else {
            $json = json_decode(file_get_contents($this->configFile), true);
        }

        $container->setParameter('kernel.packages', $this->getVersions($json));
    }

    /**
     * Extract version data from composer JSON.
     *
     * @param array $composerData
     *
     * @return array
     */
    private function getVersions(array $composerData = null)
    {
        $packages = [];

        // File was not found or invalid JSON
        if (null === $composerData) {
            return [];
        }

        foreach ($composerData as $package) {
            $name = str_replace("'", "\\'", $package['name']);

            if (!$this->addNormalizedVersion($name, $package, $packages)) {
                $this->addBranchAliasVersion($name, $package, $packages);
            }
        }

        return $packages;
    }

    /**
     * Adds version information from "version_normalized".
     *
     * @param string $name     The name of the package
     * @param array  $package  The package configuration
     * @param array  $packages All packages and versions
     *
     * @return bool Wether a version was found and added
     */
    private function addNormalizedVersion($name, array $package, array &$packages)
    {
        $version = substr($package['version_normalized'], 0, strrpos($package['version_normalized'], '.'));

        if ($this->isValidVersion($version)) {
            $packages[$name] = $version;

            return true;
        }

        return false;
    }

    /**
     * Adds version information from branch alias.
     *
     * @param string $name     The name of the package
     * @param array  $package  The package configuration
     * @param array  $packages All packages and versions
     *
     * @return bool Wether a version was found and added
     */
    private function addBranchAliasVersion($name, array $package, array &$packages)
    {
        if (isset($package['extra']['branch-alias'][$package['version_normalized']])) {
            $version = str_replace('x-dev', '9999999', $package['extra']['branch-alias'][$package['version_normalized']]);

            if ($this->isValidVersion($version)) {
                $packages[$name] = $version;

                return true;
            }
        }

        return false;
    }

    /**
     * Returns wether the given value is a valid version.
     *
     * @param string $value The version value
     *
     * @return bool Wether the value is a valid version
     */
    private function isValidVersion($value)
    {
        return (bool) preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $value);
    }
}
