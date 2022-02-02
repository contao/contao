<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Compiler;

use Composer\InstalledVersions;
use Contao\CoreBundle\Util\PackageUtil;
use PackageVersions\Versions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds the composer packages and version numbers to the container.
 *
 * @internal
 */
class AddPackagesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $packages = [];

        // The Versions::VERSIONS constant is empty since Composer 2.2.5
        if (method_exists(InstalledVersions::class, 'getAllRawData')) {
            foreach (InstalledVersions::getAllRawData() as $installed) {
                foreach ($installed['versions'] as $name => $version) {
                    if (isset($version['pretty_version'])) {
                        $packages[$name] = ltrim($version['pretty_version'], 'v');
                    }
                }
            }
        } else {
            foreach (Versions::VERSIONS as $name => $version) {
                $packages[$name] = PackageUtil::parseVersion($version);
            }
        }

        $container->setParameter('kernel.packages', $packages);
    }
}
