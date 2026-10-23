<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Relation;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Relation\RelationReference;
use Contao\ApiBundle\Relation\VirtualFilesystemRelationDefinition;
use Contao\ApiBundle\Relation\VirtualFilesystemRelationResolver;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Uuid;

final class VirtualFilesystemRelationResolverTest extends TestCase
{
    #[DataProvider('provideMetadataLocations')]
    public function testSelectsTheOperationAndStorageForTheDeclaredFilesystem(bool $onResource): void
    {
        $uuid = '12345678-1234-1234-8234-123456789abc';

        $item = $this->createStub(FilesystemItem::class);
        $item
            ->method('getUuid')
            ->willReturn(Uuid::fromString($uuid))
        ;

        $testFilesystem = $this->createMock(VirtualFilesystemInterface::class);
        $testFilesystem
            ->expects($this->once())
            ->method('get')
            ->with('archive.zip')
            ->willReturn($item)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('test_files_get', ['pathOrUuid' => $uuid])
            ->willReturn('/api/test-files/'.$uuid)
        ;

        $router
            ->method('match')
            ->willReturn(['_route' => 'test_files_get', 'pathOrUuid' => 'archive.zip'])
        ;

        $resources = [$this->resource('files', $onResource), $this->resource('test_files', $onResource)];
        $resolver = $this->createResolver($resources, $testFilesystem, $router);
        $relation = new VirtualFilesystemRelationDefinition('test_files');

        $this->assertTrue($resolver->supports($relation));
        $this->assertEquals(new RelationReference($uuid, '/api/test-files/'.$uuid), $resolver->resolveToReference($uuid, $relation));
        $this->assertSame($uuid, $resolver->resolveToIdentifier(['iri' => '/api/test-files/archive.zip'], $relation));
    }

    public function testResolvesRelationsThroughTheExposedPermissionCheckingFilesystem(): void
    {
        $filesystem = $this->createMock(VirtualFilesystem::class);
        $filesystem
            ->expects($this->never())
            ->method('get')
        ;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('match')
            ->willReturn(['_route' => 'files_get', 'pathOrUuid' => 'private.txt'])
        ;

        $resource = new ApiResource(operations: [new Get(
            name: 'files_get',
            extraProperties: ['contao' => ['filesystem' => 'test_files', 'relation_filesystem' => 'files']],
        )]);

        $resolver = $this->createResolver([$resource], new PermissionCheckingVirtualFilesystem($filesystem, $this->createStub(Security::class)), $router);
        $relation = new VirtualFilesystemRelationDefinition('files');

        $this->assertTrue($resolver->supports($relation));
        $this->expectException(AccessDeniedException::class);
        $resolver->resolveToIdentifier('/api/files/private.txt', $relation);
    }

    public function testRejectsReferencesToADifferentFilesystemOperation(): void
    {
        $testFilesystem = $this->createMock(VirtualFilesystemInterface::class);
        $testFilesystem
            ->expects($this->never())
            ->method('get')
        ;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('match')
            ->willReturn(['_route' => 'files_get', 'pathOrUuid' => 'archive.zip'])
        ;

        $resolver = $this->createResolver([$this->resource('test_files', false)], $testFilesystem, $router);

        $this->expectException(UnprocessableEntityHttpException::class);
        $resolver->resolveToIdentifier(['iri' => '/api/files/archive.zip'], new VirtualFilesystemRelationDefinition('test_files'));
    }

    public function testRejectsAnUnregisteredFile(): void
    {
        $testFilesystem = $this->createMock(VirtualFilesystemInterface::class);
        $testFilesystem
            ->expects($this->once())
            ->method('get')
            ->with('missing.jpg')
            ->willReturn(null)
        ;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('match')
            ->willReturn(['_route' => 'test_files_get', 'pathOrUuid' => 'missing.jpg'])
        ;

        $resolver = $this->createResolver([$this->resource('test_files', false)], $testFilesystem, $router);

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('does not identify a registered file or directory');

        $resolver->resolveToIdentifier(['iri' => '/api/test-files/missing.jpg'], new VirtualFilesystemRelationDefinition('test_files'));
    }

    public function testRequiresAnExposedReadOperation(): void
    {
        $testFilesystem = $this->createMock(VirtualFilesystemInterface::class);
        $testFilesystem
            ->expects($this->never())
            ->method('get')
        ;

        $resolver = $this->createResolver([$this->resource('files', false)], $testFilesystem, $this->createStub(RouterInterface::class));
        $relation = new VirtualFilesystemRelationDefinition('test_files');

        $this->assertFalse($resolver->supports($relation));
        $this->assertSame('archive.zip', $resolver->resolveToReference('archive.zip', $relation));
    }

    public function testDoesNotSupportAnotherExposedFilesystem(): void
    {
        $filesystem = $this->createMock(VirtualFilesystemInterface::class);
        $filesystem
            ->expects($this->never())
            ->method('get')
        ;

        $resolver = $this->createResolver([$this->resource('files', false), $this->resource('test_files', false)], $filesystem, $this->createStub(RouterInterface::class));
        $relation = new VirtualFilesystemRelationDefinition('files');

        $this->assertFalse($resolver->supports($relation));
        $this->assertSame('archive.zip', $resolver->resolveToReference('archive.zip', $relation));
    }

    public static function provideMetadataLocations(): iterable
    {
        yield 'operation metadata' => [false];
        yield 'resource metadata' => [true];
    }

    private function resource(string $filesystem, bool $onResource): ApiResource
    {
        $metadata = ['contao' => ['filesystem' => $filesystem]];

        return new ApiResource(
            operations: [new Get(name: $filesystem.'_get', extraProperties: $onResource ? [] : $metadata)],
            extraProperties: $onResource ? $metadata : [],
        );
    }

    /**
     * @param list<ApiResource> $resources
     */
    private function createResolver(array $resources, VirtualFilesystemInterface $storage, RouterInterface $router): VirtualFilesystemRelationResolver
    {
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(VirtualFilesystemItem::class, $resources))
        ;

        return new VirtualFilesystemRelationResolver($metadata, $router, new VirtualFilesystemRegistry(new ServiceLocator(['test_files' => static fn () => $storage]), ['test_files' => []]));
    }
}
