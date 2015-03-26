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
        $json     = json_decode(file_get_contents($this->configFile), true);
        $packages = $this->getVersions($json);

        $container->setParameter('kernel.packages', $packages);
    }

    /**
     * Extract version data from composer JSON.
     *
     * @param array $composerData
     *
     * @return array
     */
    private function getVersions(array $composerData)
    {
        $packages = [];

        // File was not found or invalid JSON
        if (null === $composerData) {
            return [];
        }

        foreach ($composerData as $package) {
            $name = str_replace("'", "\\'", $package['name']);
            $version = substr($package['version_normalized'], 0, strrpos($package['version_normalized'], '.'));

            if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $version)) {
                $packages[$name] = $version;
            } elseif (isset($package['extra']['branch-alias'][$package['version_normalized']])) {
                // FIXME: this is wrong, 4.0.x-dev would result in 4.0.999999
                $version = str_replace('x-dev', '9999999', $package['extra']['branch-alias'][$package['version_normalized']]);

                if (preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $version)) {
                    $packages[$name] = $version;
                }
            }
        }

        return $packages;
    }
}