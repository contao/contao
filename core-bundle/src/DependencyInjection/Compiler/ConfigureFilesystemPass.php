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
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class ConfigureFilesystemPass extends AbstractConfigureFilesystemPass
{
    public function process(ContainerBuilder $container): void
    {
        parent::process($container);

        $config = new FilesystemConfiguration($container);

        foreach ($this->getExtensionsThatConfigureTheFilesystem($container) as $extension) {
            trigger_deprecation(
                'contao/core-bundle',
                '5.7',
                'Implementing "%s" in the bundle extension "%s" is deprecated and will be removed in Contao 7. Register your a compiler pass that extends from %s instead.',
                ConfigureFilesystemInterface::class,
                $extension::class,
                AbstractConfigureFilesystemPass::class,
            );

            $extension->configureFilesystem($config);
        }

        $symlinkedLocalFilesProvider = $container->getDefinition('contao.filesystem.public_uri.symlinked_local_files_provider');

        $this->mountAdaptersForSymlinks($container, $config, $symlinkedLocalFilesProvider);
    }

    public function configureFilesystem(FilesystemConfiguration $config): void
    {
        // TODO: Deprecate the "contao.upload_path" config key. In the next major
        // version, $uploadPath can then be replaced with "files" and the redundant
        // "files" attribute removed when mounting the local adapter.
        $uploadPath = $config->getContainer()->getParameterBag()->resolveValue('%contao.upload_path%');

        // User uploads
        $config
            ->mountLocalAdapter($uploadPath, $uploadPath, 'files')
            ->addVirtualFilesystem($filesStorageName = 'files', $uploadPath)
            ->setPublic(true)
        ;

        $config
            ->addDefaultDbafs($filesStorageName, 'tl_files')
            ->addMethodCall('setDatabasePathPrefix', [$uploadPath]) // Backwards compatibility
        ;

        $config->addVirtualFilesystem($readonlyFilesStorageName = "$filesStorageName#readonly", $uploadPath, true);
        $config->addAssetPackage($readonlyFilesStorageName, $filesStorageName);

        // Backups
        $config
            ->mountLocalAdapter('var/backups', 'backups', 'backups')
            ->addVirtualFilesystem('backups', 'backups')
        ;

        // Job attachments
        $config
            ->mountLocalAdapter('var/job-attachments', 'job-attachments', 'job-attachments')
            ->addVirtualFilesystem('job-attachments', 'job-attachments')
        ;

        // User templates
        $config
            ->mountLocalAdapter('templates', 'user_templates', 'user_templates')
            ->addVirtualFilesystem('user_templates', 'user_templates')
        ;
    }

    /**
     * @return array<ConfigureFilesystemInterface>
     */
    private function getExtensionsThatConfigureTheFilesystem(ContainerBuilder $container): array
    {
        return array_filter(
            $container->getExtensions(),
            static fn (ExtensionInterface $extension): bool => $extension instanceof ConfigureFilesystemInterface,
        );
    }

    /**
     * Flysystem does not support symlinks, but we can use the concept of "mounting"
     * instead. For backwards compatibility, we therefore mount a local adapter for
     * each symlink found in the upload directory.
     */
    private function mountAdaptersForSymlinks(ContainerBuilder $container, FilesystemConfiguration $config, Definition $symlinkedLocalFilesProvider): void
    {
        $parameterBag = $container->getParameterBag();
        $projectDir = $parameterBag->resolveValue($parameterBag->get('kernel.project_dir'));
        $uploadDir = $parameterBag->resolveValue($parameterBag->get('contao.upload_path'));

        try {
            $finder = (new Finder())->in(Path::join($projectDir, $uploadDir))->directories();
        } catch (DirectoryNotFoundException) {
            return;
        }

        foreach ($finder as $item) {
            if (!$item->isLink()) {
                continue;
            }

            // Get absolute link target
            $target = $item->getLinkTarget();

            if (Path::isRelative($target)) {
                $target = Path::join($item->getPath(), $target);
            }

            // Mount a local adapter in place of the symlink and register it in the default
            // public URI provider
            $mountPath = Path::join($uploadDir, $item->getRelativePathname());
            $name = str_replace(['.', '/', '-'], '_', Container::underscore($mountPath));
            $adapterId = "contao.filesystem.adapter.$name";

            $config->mountLocalAdapter($target, $mountPath, $name);
            $symlinkedLocalFilesProvider->addMethodCall('registerAdapter', [new Reference($adapterId), $mountPath]);
        }
    }
}
