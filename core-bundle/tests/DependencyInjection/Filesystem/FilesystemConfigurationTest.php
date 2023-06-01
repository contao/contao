<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Filesystem;

use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use League\FlysystemBundle\Adapter\AdapterDefinitionFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class FilesystemConfigurationTest extends TestCase
{
    public function testGetContainer(): void
    {
        $container = $this->createMock(ContainerBuilder::class);
        $config = new FilesystemConfiguration($container);

        $this->assertSame($container, $config->getContainer());
    }

    /**
     * @dataProvider provideReadOnlyValues
     */
    public function testAddVirtualFilesystem(bool $readOnly): void
    {
        $container = $this->getContainerBuilder();
        $config = new FilesystemConfiguration($container);
        $definition = $config->addVirtualFilesystem('foo', 'some/prefix', $readOnly);

        $this->assertTrue($container->hasDefinition('contao.filesystem.virtual.foo'));
        $this->assertTrue($container->hasAlias(VirtualFilesystemInterface::class.' $fooStorage'));

        $this->assertSame($definition, $container->getDefinition('contao.filesystem.virtual.foo'));
        $this->assertSame(VirtualFilesystem::class, $definition->getClass());
        $this->assertSame('contao.filesystem.virtual_factory', (string) $definition->getFactory()[0]);

        $this->assertSame(['some/prefix', $readOnly], $definition->getArguments());
        $this->assertTrue($definition->hasTag('contao.virtual_filesystem'));
        $this->assertSame([['name' => 'foo', 'prefix' => 'some/prefix']], $definition->getTag('contao.virtual_filesystem'));
    }

    public function provideReadOnlyValues(): \Generator
    {
        yield 'protected' => [true];
        yield 'accessible' => [false];
    }

    public function testDisallowsAddingAnotherVirtualFilesystemWithTheSameName(): void
    {
        $config = new FilesystemConfiguration($this->getContainerBuilder());
        $config->addVirtualFilesystem('foo', 'bar');

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A virtual filesystem with the name "foo" is already defined.');

        $config->addVirtualFilesystem('foo', 'bar');
    }

    public function testMountNativeAdapter(): void
    {
        $container = $this->getContainerBuilder();

        $adapterDefinition = $this->createMock(Definition::class);
        $adapterDefinition
            ->expects($this->once())
            ->method('setPublic')
            ->with(false)
        ;

        $adapterDefinitionFactory = $this->createMock(AdapterDefinitionFactory::class);
        $adapterDefinitionFactory
            ->method('createDefinition')
            ->with('some-native-adapter', ['some' => 'options'])
            ->willReturn($adapterDefinition)
        ;

        $config = $this->getConfigurationWithAdapterDefinitionFactory($container, $adapterDefinitionFactory);
        $config->mountAdapter('some-native-adapter', ['some' => 'options'], 'path', 'foo');

        $this->assertTrue($container->hasDefinition('contao.filesystem.adapter.foo'));
        $this->assertSame($adapterDefinition, $container->getDefinition('contao.filesystem.adapter.foo'));

        $calls = $container->getDefinition('contao.filesystem.mount_manager')->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertSame('mount', $calls[0][0]);
        $this->assertSame('contao.filesystem.adapter.foo', (string) $calls[0][1][0]);
        $this->assertSame('path', $calls[0][1][1]);
    }

    public function testMountCustomAdapter(): void
    {
        $container = $this->getContainerBuilder();

        $adapterDefinitionFactory = $this->createMock(AdapterDefinitionFactory::class);
        $adapterDefinitionFactory
            ->method('createDefinition')
            ->with('some-custom-adapter', ['some' => 'options'])
            ->willReturn(null)
        ;

        $config = $this->getConfigurationWithAdapterDefinitionFactory($container, $adapterDefinitionFactory);
        $config->mountAdapter('some-custom-adapter', ['some' => 'options'], 'path', 'foo');

        $this->assertTrue($container->hasAlias('contao.filesystem.adapter.foo'));
        $this->assertFalse($container->getAlias('contao.filesystem.adapter.foo')->isPublic());

        $calls = $container->getDefinition('contao.filesystem.mount_manager')->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertSame('mount', $calls[0][0]);
        $this->assertSame('contao.filesystem.adapter.foo', (string) $calls[0][1][0]);
        $this->assertSame('path', $calls[0][1][1]);
    }

    /**
     * @dataProvider provideMountPaths
     */
    public function testMountAdapterAutoGeneratesId(string $mountPath, string $expectedId): void
    {
        $container = $this->getContainerBuilder();
        $config = new FilesystemConfiguration($container);

        $this->assertFalse($container->hasAlias($expectedId));

        $config->mountAdapter('foo', [], $mountPath);

        $this->assertTrue($container->hasAlias($expectedId));
    }

    public function provideMountPaths(): \Generator
    {
        yield 'single folder' => [
            'files',
            'contao.filesystem.adapter.files',
        ];

        yield 'nested folder' => [
            'foo/bar/baz',
            'contao.filesystem.adapter.foo_bar_baz',
        ];

        yield 'with special chars' => [
            'some.where/over-the_rainbow',
            'contao.filesystem.adapter.some_where_over_the_rainbow',
        ];
    }

    /**
     * @dataProvider provideFilesystemPaths
     */
    public function testMountLocalAdapterNormalizesPath(string $filesystemPath, string $expected): void
    {
        $container = $this->getContainerBuilder([
            'kernel.project_dir' => '/my/site',
            'bar' => 'path/to/bar',
        ]);

        $adapterDefinitionFactory = $this->createMock(AdapterDefinitionFactory::class);
        $adapterDefinitionFactory
            ->method('createDefinition')
            ->with('local', ['directory' => $expected, 'skip_links' => true])
            ->willReturn($this->createMock(Definition::class))
        ;

        $config = $this->getConfigurationWithAdapterDefinitionFactory($container, $adapterDefinitionFactory);
        $config->mountLocalAdapter($filesystemPath, 'mount/path', 'my_adapter');

        $this->assertTrue($container->hasDefinition('contao.filesystem.adapter.my_adapter'));
    }

    public function provideFilesystemPaths(): \Generator
    {
        yield 'absolute path' => [
            '/my/site/files',
            '/my/site/files',
        ];

        yield 'relative path' => [
            'foobar',
            '/my/site/foobar',
        ];

        yield 'with placeholders' => [
            'foo/%bar%',
            '/my/site/foo/path/to/bar',
        ];
    }

    public function testRegisterDbafs(): void
    {
        $container = $this->getContainerBuilder();
        $dbafsDefinition = $this->createMock(Definition::class);

        $config = new FilesystemConfiguration($container);
        $config->registerDbafs($dbafsDefinition, 'foo/bar');

        $calls = $container->getDefinition('contao.filesystem.dbafs_manager')->getMethodCalls();

        $this->assertCount(1, $calls);
        $this->assertSame(['register', [$dbafsDefinition, 'foo/bar']], $calls[0]);
    }

    /**
     * @dataProvider provideUseLastModifiedValues
     */
    public function testAddDefaultDbafs(bool $useLastModified): void
    {
        $container = $this->getContainerBuilder();

        $config = new FilesystemConfiguration($container);
        $config->addVirtualFilesystem('foo', 'some/prefix');

        $definition = $config->addDefaultDbafs('foo', 'tl_foo', 'xxh32', $useLastModified);

        // Hash generator
        $this->assertTrue($container->hasDefinition('contao.filesystem.hash_generator.foo'));
        $hashGenerator = $container->getDefinition('contao.filesystem.hash_generator.foo');
        $this->assertSame(HashGenerator::class, $hashGenerator->getClass());
        $this->assertSame(['xxh32', $useLastModified], $hashGenerator->getArguments());

        // DBAFS
        $this->assertTrue($container->hasDefinition('contao.filesystem.dbafs.foo'));
        $dbafs = $container->getDefinition('contao.filesystem.dbafs.foo');
        $this->assertSame(Dbafs::class, $dbafs->getClass());
        $this->assertSame('contao.filesystem.virtual.foo', (string) $dbafs->getArgument(0));
        $this->assertSame('contao.filesystem.hash_generator.foo', (string) $dbafs->getArgument(1));
        $this->assertSame('tl_foo', $dbafs->getArgument(2));
        $this->assertTrue($definition->hasTag('kernel.reset'));

        // Set last modified
        $this->assertSame(
            ['useLastModified', [$useLastModified]],
            $definition->getMethodCalls()[0]
        );

        // Registered at DbafsManager
        $this->assertSame(
            ['register', [$definition, 'some/prefix']],
            $container->getDefinition('contao.filesystem.dbafs_manager')->getMethodCalls()[0]
        );
    }

    public function provideUseLastModifiedValues(): \Generator
    {
        yield 'use last modified' => [true];
        yield 'do not use last modified' => [false];
    }

    public function testAddDefaultDbafsFailsIfVirtualFilesystemDoesNotExist(): void
    {
        $container = $this->getContainerBuilder();
        $config = new FilesystemConfiguration($container);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A virtual filesystem with the name "foo" does not exist.');

        $config->addDefaultDbafs('foo', 'tl_foo');
    }

    private function getConfigurationWithAdapterDefinitionFactory(ContainerBuilder $container, AdapterDefinitionFactory $adapterDefinitionFactory): FilesystemConfiguration
    {
        $config = new FilesystemConfiguration($container);
        $config->setAdapterDefinitionFactory($adapterDefinitionFactory);

        return $config;
    }

    private function getContainerBuilder(array $parameters = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag($parameters));
        $container->setDefinition('contao.filesystem.mount_manager', new Definition(MountManager::class));
        $container->setDefinition('contao.filesystem.dbafs_manager', new Definition(DbafsManager::class));

        return $container;
    }
}
