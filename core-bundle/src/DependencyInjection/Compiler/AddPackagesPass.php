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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds the composer packages and version numbers to the container.
 *
 * @internal
 *
 * @deprecated Deprecated since Contao 4.5, to be removed in Contao 5.0; use
 *             the Composer\InstalledVersions class instead
 */
class AddPackagesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $packages = [];

        foreach (InstalledVersions::getAllRawData() as $installed) {
            foreach ($installed['versions'] as $name => $version) {
                if (isset($version['pretty_version'])) {
                    $packages[$name] = ltrim($version['pretty_version'], 'v');
                }
            }
        }

        $container->setParameter('kernel.packages', $packages);
    }
}
