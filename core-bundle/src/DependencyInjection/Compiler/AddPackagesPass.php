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

/**
 * Adds the composer packages and versions to the container.
 *
 * @author Andreas Schempp <https://github.com/aschempp>
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddPackagesPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $jsonFile;

    /**
     * Constructor.
     *
     * @param string $jsonFile Path to the composer installed.json file
     */
    public function __construct($jsonFile)
    {
        $this->jsonFile = $jsonFile;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $packages = [];

        if (is_file($this->jsonFile)) {
            $json = json_decode(file_get_contents($this->jsonFile), true);

            if (null !== $json) {
                $packages = $this->getVersions($json);
            }
        }

        $container->setParameter('kernel.packages', $packages);
    }

    /**
     * Extracts the version numbers from the JSON data.
     *
     * @param array $json The JSON array
     *
     * @return array The packages array
     */
    private function getVersions(array $json)
    {
        $packages = [];

        foreach ($json as $package) {
            $this->addVersion($package, $packages);
        }

        return $packages;
    }

    /**
     * Adds a version to the packages array.
     *
     * @param array $package  The package
     * @param array $packages The packages array
     */
    private function addVersion(array $package, array &$packages)
    {
        if (true === $this->addNormalizedVersion($package, $packages)) {
            return;
        }

        $this->addBranchAliasVersion($package, $packages);
    }

    /**
     * Adds the normalized version.
     *
     * @param array $package  The package
     * @param array $packages The packages array
     *
     * @return bool True if a version was found and added
     */
    private function addNormalizedVersion(array $package, array &$packages)
    {
        $version = substr($package['version_normalized'], 0, strrpos($package['version_normalized'], '.'));

        if (!$this->isValidVersion($version)) {
            return false;
        }

        $packages[$package['name']] = $version;

        return true;
    }

    /**
     * Adds the branch alias version.
     *
     * @param array $package  The package
     * @param array $packages The packages array
     *
     * @return bool True if a version was found and added
     */
    private function addBranchAliasVersion(array $package, array &$packages)
    {
        if (!isset($package['extra']['branch-alias'][$package['version_normalized']])) {
            return false;
        }

        $version = $package['extra']['branch-alias'][$package['version_normalized']];

        if (!$this->isValidVersion($version)) {
            return false;
        }

        $packages[$package['name']] = $version;

        return true;
    }

    /**
     * Validates a version number.
     *
     * @param string $version The version number
     *
     * @return bool True the version number is valid
     */
    private function isValidVersion($version)
    {
        return (bool) preg_match('/^[0-9]+\.[0-9]+\.([0-9]+|x-dev)$/', $version);
    }
}
