<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds the composer packages and version numbers to the container.
 */
class AddPackagesPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $jsonFile;

    /**
     * @param string $jsonFile
     */
    public function __construct(string $jsonFile)
    {
        $this->jsonFile = $jsonFile;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
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
     * @param array $json
     *
     * @return array
     */
    private function getVersions(array $json): array
    {
        $packages = [];

        foreach ($json as $package) {
            if (isset($package['version'])) {
                if (isset($package['extra']['branch-alias'][$package['version']])) {
                    $packages[$package['name']] = $package['extra']['branch-alias'][$package['version']];
                } else {
                    $packages[$package['name']] = $package['version'];
                }
            }
        }

        return $packages;
    }
}
