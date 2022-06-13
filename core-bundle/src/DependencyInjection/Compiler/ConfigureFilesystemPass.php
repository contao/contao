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
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

class ConfigureFilesystemPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $config = new FilesystemConfiguration($container);

        foreach ($this->getExtensionsThatConfigureTheFilesystem($container) as $extension) {
            $extension->configureFilesystem($config);
        }

        $this->mountAdaptersForSymlinks($container, $config);
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

    /**
     * Flysystem does not support symlinks, but we can use the concept of
     * "mounting" instead. For backwards compatibility, we therefore mount
     * a local adapter for each symlink found in the upload directory.
     */
    private function mountAdaptersForSymlinks(ContainerBuilder $container, FilesystemConfiguration $config): void
    {
        $parameterBag = $container->getParameterBag();
        $projectDir = $parameterBag->resolveValue($parameterBag->get('kernel.project_dir'));
        $uploadDir = $parameterBag->resolveValue($parameterBag->get('contao.upload_path'));

        $finder = (new Finder())->in($projectDir)->directories()->path('/^'.preg_quote($uploadDir, '/').'/');

        foreach ($finder as $item) {
            if (!$item->isLink()) {
                continue;
            }

            // Get absolute link target
            $target = $item->getLinkTarget();

            if (Path::isRelative($target)) {
                $target = Path::join($item->getPath(), $target);
            }

            // Mount a local adapter in place of the symlink
            $config->mountLocalAdapter($target, $item->getRelativePathname());
        }
    }
}
