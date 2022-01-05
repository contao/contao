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

use Contao\CoreBundle\DependencyInjection\Filesystem\ConfigureFilesystemInterface;
use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ConfigureFilesystemPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $config = new FilesystemConfiguration($container);

        foreach ($this->getExtensionsThatConfigureTheFilesystem($container) as $extension) {
            $extension->configureFilesystem($config);
        }
    }

    /**
     * @return array<ConfigureFilesystemInterface>
     */
    private function getExtensionsThatConfigureTheFilesystem(ContainerBuilder $container): array
    {
        return array_filter(
            $container->getExtensions(),
            static fn (ExtensionInterface $extension): bool => $extension instanceof ConfigureFilesystemInterface
        );
    }
}
