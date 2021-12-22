<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\DependencyInjection\Filesystem;

use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsFactory;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemFactory;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class FilesystemConfig
{
    private ContainerBuilder $container;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
    }

    /**
     * Add another new VirtualFilesystem service.
     *
     *  // todo service name
     * Setting the name to 'foo' will create a 'contao.filesystem.virtual.foo'
     * service and additionally enable constructor injection with an argument
     * 'VirtualFilesystemInterface $fooStorage' if autowiring is available.
     */
    public function addVirtualFilesystem(string $name, string $prefix, bool $readonly = false): self
    {
        $definition = new Definition(VirtualFilesystem::class, [$prefix, $readonly]);
        $definition->setFactory(new Reference(VirtualFilesystemFactory::class));

        $this->container->setDefinition($id = "contao.filesystem.virtual.$name", $definition);
        $this->container->registerAliasForArgument($id, VirtualFilesystemInterface::class, "{$name}Storage");

        return $this;
    }

    /**
     * @param Reference|string $adapter
     */
    public function mountAdapter($adapter, string $path, array $config = []): self
    {
        // todo … adapter ->

        $this->container
            ->getDefinition('contao.filesystem.mount_manager')
            ->addMethodCall('mount', [$adapter, $path])
        ;

        return $this;
    }

    public function mountLocalAdapter(string $mountPath, string $filesystemPath): self
    {
        // todo …

        // 'adapter' => 'local',
        // 'options' => [
        //     'directory' => $filesystemPath,
        // ],

        return $this;
    }

    public function registerDbafs(Definition $dbafs, string $pathPrefix): self
    {
        $this->container
            ->getDefinition('contao.filesystem.dbafs.dbafs_manager')
            ->addMethodCall('register', [$dbafs, $pathPrefix])
        ;

        return $this;
    }

    public function addDefaultDbafs(
        string $pathPrefix,
        string $table,
        string $hashFunction = 'md5',
        int $maxFileSize = 2147483648,
        int $bulkInsertSize = 100,
        bool $useLastModified = true,
        string $databasePrefix = ''
    ): self
    {
        $definition = new Definition(Dbafs::class, []);
        $definition->setFactory(new Reference(DbafsFactory::class));
        // … todo

        $this->registerDbafs($definition, $pathPrefix);

        return $this;
    }
}
