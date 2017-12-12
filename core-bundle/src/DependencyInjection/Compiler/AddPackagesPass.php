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

use Contao\CoreBundle\Util\PackageUtil;
use PackageVersions\Versions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds the composer packages and version numbers to the container.
 */
class AddPackagesPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $packages = [];

        foreach (Versions::VERSIONS as $name => $version) {
            $packages[$name] = PackageUtil::parseVersion($version);
        }

        $container->setParameter('kernel.packages', $packages);
    }
}
