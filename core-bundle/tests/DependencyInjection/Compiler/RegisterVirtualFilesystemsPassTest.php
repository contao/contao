<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DependencyInjection\Compiler;

use Contao\CoreBundle\DependencyInjection\Compiler\RegisterVirtualFilesystemsPass;
use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemFactory;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RegisterVirtualFilesystemsPassTest extends TestCase
{
    public function testRegistersPrivateFactoryServices(): void
    {
        $container = new ContainerBuilder();
        $container->register('contao.filesystem.virtual_registry', VirtualFilesystemRegistry::class)->setPublic(true);
        $container->register('contao.filesystem.virtual_factory', VirtualFilesystemFactory::class)
            ->setArguments([
                new Reference('contao.filesystem.mount_manager'),
                new Reference('contao.filesystem.dbafs_manager'),
            ])
        ;
        $container->register('contao.filesystem.mount_manager', MountManager::class)->setSynthetic(true)->setPublic(true);
        $container->register('contao.filesystem.dbafs_manager', DbafsManager::class)->setSynthetic(true)->setPublic(true);

        $config = new FilesystemConfiguration($container);
        $config->addVirtualFilesystem('files', 'files');
        $config->addVirtualFilesystem('backups', 'backups');
        $config->addVirtualFilesystem('files#readonly', 'files', true);

        $container->addCompilerPass(new RegisterVirtualFilesystemsPass());
        $container->compile();

        $registry = $container->get('contao.filesystem.virtual_registry');

        $this->assertInstanceOf(VirtualFilesystemRegistry::class, $registry);
        $this->assertSame(['files' => ['prefix' => 'files'], 'backups' => ['prefix' => 'backups'], 'files#readonly' => ['prefix' => 'files']], $registry->findByTags());
        $this->assertFalse($container->initialized('contao.filesystem.virtual_factory'));

        $container->set('contao.filesystem.mount_manager', $this->createStub(MountManager::class));
        $container->set('contao.filesystem.dbafs_manager', $this->createStub(DbafsManager::class));

        $files = $registry->get('files');
        $readonlyFiles = $registry->get('files#readonly');

        $this->assertInstanceOf(VirtualFilesystem::class, $files);
        $this->assertInstanceOf(VirtualFilesystem::class, $readonlyFiles);
        $this->assertSame('files', $files->getPrefix());
        $this->assertFalse($files->isReadOnly());
        $this->assertTrue($readonlyFiles->isReadOnly());
        $this->assertSame($files, $registry->get('files'));
    }

    public function testCompilesRegistryWithLazyServicesAndCompleteMetadata(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('vfs_name', 'files');
        $container->setParameter('vfs_path', '/files');
        $container->register('contao.filesystem.virtual_registry', VirtualFilesystemRegistry::class)->setPublic(true);
        $container->addCompilerPass(new RegisterVirtualFilesystemsPass());

        $files = new Definition(VirtualFilesystemInterface::class)
            ->setSynthetic(true)
            ->setPublic(true)
            ->addTag('contao.virtual_filesystem', ['name' => '%vfs_name%', 'prefix' => 'files', 'api_exposure' => true, 'api_path' => '%vfs_path%', 'label' => '%%files%%'])
        ;
        $container->setDefinition('files', $files);
        $container->setDefinition('backups', new Definition(VirtualFilesystemInterface::class)->setSynthetic(true)->setPublic(true)->addTag('contao.virtual_filesystem', ['name' => 'backups']));
        $container->compile();

        $registry = $container->get('contao.filesystem.virtual_registry');

        $this->assertInstanceOf(VirtualFilesystemRegistry::class, $registry);
        $this->assertSame(['files' => ['prefix' => 'files', 'api_exposure' => true, 'api_path' => '/files', 'label' => '%files%']], $registry->findByTags(['api_exposure' => true]));
        $this->assertSame([], $registry->getTags('backups'));
        $this->assertFalse($container->initialized('files'));

        $filesystem = $this->createStub(VirtualFilesystemInterface::class);
        $container->set('files', $filesystem);

        $this->assertSame($filesystem, $registry->get('files'));
    }

    #[DataProvider('provideInvalidNames')]
    public function testRejectsInvalidIdentifiers(array $attributes): void
    {
        $container = new ContainerBuilder();
        $container->register('contao.filesystem.virtual_registry', VirtualFilesystemRegistry::class);
        $container->register('invalid', VirtualFilesystemInterface::class)->addTag('contao.virtual_filesystem', $attributes);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('must have a non-empty string name without "/"');

        new RegisterVirtualFilesystemsPass()->process($container);
    }

    public static function provideInvalidNames(): iterable
    {
        yield 'missing' => [[]];
        yield 'empty' => [['name' => '']];
        yield 'slash' => [['name' => 'files/images']];
        yield 'integer' => [['name' => 123]];
    }

    #[DataProvider('provideDuplicateServices')]
    public function testRejectsDuplicateIdentifiers(bool $sameService): void
    {
        $container = new ContainerBuilder();
        $container->register('contao.filesystem.virtual_registry', VirtualFilesystemRegistry::class);

        $definition = $container->register('first', VirtualFilesystemInterface::class);
        $definition->addTag('contao.virtual_filesystem', ['name' => 'files']);

        if (!$sameService) {
            $definition = $container->register('second', VirtualFilesystemInterface::class);
        }

        $definition->addTag('contao.virtual_filesystem', ['name' => 'files']);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('A virtual filesystem with the name "files" is already defined.');

        new RegisterVirtualFilesystemsPass()->process($container);
    }

    public static function provideDuplicateServices(): iterable
    {
        yield 'same service' => [true];
        yield 'different services' => [false];
    }

    public function testRegistersEmptyRegistry(): void
    {
        $container = new ContainerBuilder();
        $container->register('contao.filesystem.virtual_registry', VirtualFilesystemRegistry::class)->setPublic(true);
        $container->addCompilerPass(new RegisterVirtualFilesystemsPass());
        $container->compile();

        $this->assertSame([], $container->get('contao.filesystem.virtual_registry')->findByTags());
    }
}
