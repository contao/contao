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
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use League\FlysystemBundle\Adapter\AdapterDefinitionFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Path;

/**
 * @experimental
 */
class FilesystemConfiguration
{
    private ContainerBuilder $container;
    private AdapterDefinitionFactory $adapterDefinitionFactory;

    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->adapterDefinitionFactory = new AdapterDefinitionFactory();
    }

    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }

    /**
     * Adds another new VirtualFilesystem service.
     *
     * Setting the name to "foo" will create a "contao.filesystem.virtual.foo"
     * service and additionally enable constructor injection with an argument
     * "VirtualFilesystemInterface $fooStorage" if autowiring is available.
     *
     * @return Definition the newly created definition
     */
    public function addVirtualFilesystem(string $name, string $prefix, bool $readonly = false): Definition
    {
        if (null !== $this->getVirtualFilesystem($name)) {
            throw new InvalidConfigurationException(sprintf('A virtual filesystem with the name "%s" is already defined.', $name));
        }

        $definition = new Definition(VirtualFilesystem::class, [$prefix, $readonly]);
        $definition->setFactory(new Reference('contao.filesystem.virtual_factory'));
        $definition->addTag('contao.virtual_filesystem', compact('name', 'prefix'));

        $this->container->setDefinition($id = "contao.filesystem.virtual.$name", $definition);
        $this->container->registerAliasForArgument($id, VirtualFilesystemInterface::class, "{$name}Storage");

        return $definition;
    }

    /**
     * Mounts a new Flysystem adapter to the virtual filesystem.
     *
     * The $adapter and $options can be set analogous to the configuration of
     * the Flysystem Symfony bundle. Alternatively you can pass in an id of an
     * already existing filesystem adapter service.
     *
     * @see https://github.com/thephpleague/flysystem-bundle#basic-usage
     *
     * The $mountPath must be a path relative to and inside the project root
     * (e.g. "files/foo" or "assets/images").
     *
     * If you do not set a name, the id/alias for the adapter service will be
     * derived from the mount path.
     */
    public function mountAdapter(string $adapter, array $options, string $mountPath, string $name = null): self
    {
        $name ??= str_replace(['.', '/', '-'], '_', Container::underscore($mountPath));
        $adapterId = "contao.filesystem.adapter.$name";

        if (null !== ($adapterDefinition = $this->adapterDefinitionFactory->createDefinition($adapter, $options, null))) {
            // Native adapter
            $this->container
                ->setDefinition($adapterId, $adapterDefinition)
                ->setPublic(false)
            ;
        } else {
            // Custom adapter
            $this->container
                ->setAlias($adapterId, $adapter)
                ->setPublic(false)
            ;
        }

        $this->container
            ->getDefinition('contao.filesystem.mount_manager')
            ->addMethodCall('mount', [new Reference($adapterId), $mountPath])
        ;

        return $this;
    }

    /**
     * Shortcut method to mount a filesystem path to the virtual filesystem.
     *
     * If you want to use arbitrary adapters or options, please use
     * mountAdapter() instead.
     *
     * The $mountPath must be a path relative to and inside the project root
     * (e.g. "files/foo" or "assets/images"); the $filesystemPath can either
     * be absolute or relative to the project root and may contain
     * placeholders (%name%).
     *
     * If you do not set a name, the id for the adapter service will be derived
     * from the mount path.
     */
    public function mountLocalAdapter(string $filesystemPath, string $mountPath, string $name = null): self
    {
        $path = Path::isAbsolute($filesystemPath)
            ? Path::canonicalize($filesystemPath)
            : Path::join('%kernel.project_dir%', $filesystemPath);

        $path = $this->container->getParameterBag()->resolveValue($path);

        $this->mountAdapter(
            'local',
            [
                'directory' => $path,
                'skip_links' => true,
            ],
            Path::normalize($mountPath),
            $name
        );

        return $this;
    }

    /**
     * Registers a custom DBAFS service definition.
     *
     * This is advanced stuff. If you want to use the default implementation,
     * please use addDefaultDbafs() instead.
     */
    public function registerDbafs(Definition $dbafs, string $pathPrefix): self
    {
        $this->container
            ->getDefinition('contao.filesystem.dbafs_manager')
            ->addMethodCall('register', [$dbafs, $pathPrefix])
        ;

        return $this;
    }

    /**
     * Registers a DBAFS service with the default implementation.
     *
     * If you want to fine tune settings (e.g. adjust the bulk insert size or
     * the maximum file size) add method calls to the definition returned by
     * this method.
     *
     * @return Definition the newly created definition
     */
    public function addDefaultDbafs(string $virtualFilesystemName, string $table, string $hashFunction = 'md5', bool $useLastModified = true): Definition
    {
        if (null === ($virtualFilesystem = $this->getVirtualFilesystem($virtualFilesystemName))) {
            throw new InvalidConfigurationException(sprintf('A virtual filesystem with the name "%s" does not exist.', $virtualFilesystemName));
        }

        // Add an individual hash generator
        $this->container->setDefinition(
            $hashGeneratorId = "contao.filesystem.hash_generator.$virtualFilesystemName",
            new Definition(HashGenerator::class, [$hashFunction, $useLastModified])
        );

        // Add the DBAFS service
        [$virtualFilesystemId, $prefix] = $virtualFilesystem;

        $definition = new Definition(
            Dbafs::class,
            [new Reference($virtualFilesystemId), new Reference($hashGeneratorId), $table]
        );

        $definition->setFactory(new Reference('contao.filesystem.dbafs_factory'));
        $definition->addMethodCall('useLastModified', [$useLastModified]);
        $definition->addTag('kernel.reset', ['method' => 'reset']);

        $this->container->setDefinition("contao.filesystem.dbafs.$virtualFilesystemName", $definition);

        // Register the DBAFS in the DbafsManager using the same prefix as the
        // associated virtual filesystem
        $this->registerDbafs($definition, $prefix);

        return $definition;
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function getVirtualFilesystem(string $name): ?array
    {
        foreach ($this->container->findTaggedServiceIds('contao.virtual_filesystem') as $id => $tags) {
            foreach ($tags as $tag) {
                if ($tag['name'] === $name) {
                    return [$id, $tag['prefix']];
                }
            }
        }

        return null;
    }
}
