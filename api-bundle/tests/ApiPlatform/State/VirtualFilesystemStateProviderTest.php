<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\State;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Contao\ApiBundle\ApiPlatform\State\VirtualFilesystemStateProvider;
use Contao\ApiBundle\Dto\VirtualFilesystemItemFactory;
use Contao\ApiBundle\Serializer\SchemaAwareObjectNormalizer;
use Contao\ApiBundle\Serializer\VirtualFilesystemMetadataNormalizationHandler;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\TextTrack;
use Contao\CoreBundle\File\TextTrackType;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\PermissionCheckingVirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Contao\Image\ImportantPart;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class VirtualFilesystemStateProviderTest extends TestCase
{
    public function testUsesTheFilesystemSelectedByTheOperation(): void
    {
        $files = $this->createMock(VirtualFilesystem::class);
        $files
            ->expects($this->once())
            ->method('get')
            ->with('example.txt')
            ->willReturn(new FilesystemItem(true, 'example.txt', 1, 4, 'text/plain'))
        ;

        $testFilesystem = $this->createMock(VirtualFilesystemInterface::class);
        $testFilesystem
            ->expects($this->once())
            ->method('get')
            ->with('example.txt')
            ->willReturn(new FilesystemItem(true, 'example.txt', 1, 7, 'text/plain'))
        ;

        $provider = new VirtualFilesystemStateProvider(new VirtualFilesystemRegistry(new ServiceLocator([
            'files' => fn () => new PermissionCheckingVirtualFilesystem($files, $this->createSecurityStub()),
            'test_files' => static fn () => $testFilesystem,
        ]), ['files' => [], 'test_files' => []]), $this->createItemFactory());

        $this->assertSame(4, $provider->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'files']]), ['path' => 'example.txt'])->fileSize);
        $this->assertSame(7, $provider->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'test_files']]), ['path' => 'example.txt'])->fileSize);
    }

    public function testChecksPermissionsBeforeReadingTheFilesystem(): void
    {
        $filesystem = $this->createMock(VirtualFilesystem::class);
        $filesystem
            ->expects($this->never())
            ->method('get')
        ;

        $provider = new VirtualFilesystemStateProvider($this->createRegistry(new PermissionCheckingVirtualFilesystem($filesystem, $this->createStub(Security::class))), $this->createItemFactory());

        $this->expectException(AccessDeniedException::class);
        $provider->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'files']]), ['path' => 'example.txt']);
    }

    public function testProvidesFileMetadataIncludingDbafsData(): void
    {
        $uuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');
        $importantPart = new ImportantPart(0.1, 0.2, 0.3, 0.4);
        $textTrack = new TextTrack('en', TextTrackType::subtitles);

        $item = new FilesystemItem(
            true,
            'images/example.jpg',
            123,
            456,
            'image/jpeg',
            new ExtraMetadata([
                'uuid' => $uuid,
                'localized' => new MetadataBag(['en' => new Metadata(['title' => 'Example', 'uuid' => $uuid->toRfc4122()])]),
                'importantPart' => $importantPart,
                'textTrack' => $textTrack,
                'custom' => ['enabled' => true],
            ]),
        );

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('get')
            ->with('images/example.jpg')
            ->willReturn($item)
        ;

        $result = $this->createProvider($storage)->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'files']]), ['path' => 'images/example.jpg']);

        $this->assertSame('images/example.jpg', $result->path);
        $this->assertSame(456, $result->fileSize);
        $this->assertSame('image/jpeg', $result->mimeType);
        $this->assertSame(
            [
                'uuid' => $uuid->toRfc4122(),
                'localized' => ['en' => ['title' => 'Example', 'uuid' => $uuid->toRfc4122()]],
                'importantPart' => ['x' => 0.1, 'y' => 0.2, 'width' => 0.3, 'height' => 0.4],
                'textTrack' => ['sourceLanguage' => 'en', 'type' => 'subtitles'],
                'custom' => ['enabled' => true],
            ],
            $result->metadata,
        );
    }

    public function testListsItemsWithoutDbafsData(): void
    {
        $items = new FilesystemItemIterator([
            new FilesystemItem(true, 'documents/Guide.pdf', 123, 456, 'application/pdf'),
            new FilesystemItem(true, 'documents/notes.txt', 124, 12, 'text/plain'),
        ]);

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('listContents')
            ->with('documents', true)
            ->willReturn($items)
        ;

        $result = $this->createProvider($storage)->provide(
            new GetCollection(extraProperties: ['contao' => ['filesystem' => 'files']]),
            context: ['filters' => ['path' => 'documents', 'deep' => true]],
        );

        $this->assertCount(2, $result);
        $this->assertSame('documents/Guide.pdf', $result[0]->path);
        $this->assertSame([], $result[0]->metadata);
        $this->assertSame('documents/notes.txt', $result[1]->path);
    }

    public function testTreatsAUuidShapedPathAsALiteralPath(): void
    {
        $path = '171bb68d-0094-4f6c-88f5-9b83c0d01521';
        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->never())
            ->method('resolveUuid')
        ;

        $storage
            ->expects($this->once())
            ->method('get')
            ->with($path)
            ->willReturn(new FilesystemItem(true, $path, 123, 7, 'text/plain'))
        ;

        $result = $this->createProvider($storage)->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'files']]), ['path' => $path]);

        $this->assertSame($path, $result->path);
    }

    public function testProvidesAnItemByUuid(): void
    {
        $uuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');
        $item = new FilesystemItem(true, 'images/example.jpg', 123, 456, 'image/jpeg');

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('resolveUuid')
            ->with($uuid)
            ->willReturn('images/example.jpg')
        ;

        $storage
            ->expects($this->once())
            ->method('get')
            ->with($uuid)
            ->willReturn($item)
        ;

        $result = $this->createProvider($storage)->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'files']]), ['uuid' => $uuid->toRfc4122()]);

        $this->assertSame('images/example.jpg', $result->path);
    }

    public function testListsItemsByUuid(): void
    {
        $uuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');
        $items = new FilesystemItemIterator([new FilesystemItem(true, 'documents/example.txt', 123, 7, 'text/plain')]);

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('resolveUuid')
            ->with($uuid)
            ->willReturn('documents')
        ;

        $storage
            ->expects($this->once())
            ->method('listContents')
            ->with($uuid, false)
            ->willReturn($items)
        ;

        $result = $this->createProvider($storage)->provide(
            new GetCollection(extraProperties: ['contao' => ['filesystem' => 'files']]),
            context: ['filters' => ['uuid' => $uuid->toRfc4122()]],
        );

        $this->assertSame('documents/example.txt', $result[0]->path);
    }

    public function testReturnsNotFoundForAnUnresolvedUuid(): void
    {
        $uuid = Uuid::fromString('171bb68d-0094-4f6c-88f5-9b83c0d01521');

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->expects($this->once())
            ->method('resolveUuid')
            ->with($uuid)
            ->willThrowException(new UnableToResolveUuidException($uuid))
        ;

        $this->expectException(NotFoundHttpException::class);

        $this->createProvider($storage)->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'files']]), ['uuid' => $uuid->toRfc4122()]);
    }

    public function testReturnsNotFoundForAMissingItem(): void
    {
        $storage = $this->createStub(VirtualFilesystem::class);

        $this->expectException(NotFoundHttpException::class);
        $this->createProvider($storage)->provide(new Get(extraProperties: ['contao' => ['filesystem' => 'files']]), ['path' => 'missing.txt']);
    }

    private function createProvider(VirtualFilesystem $storage): VirtualFilesystemStateProvider
    {
        return new VirtualFilesystemStateProvider($this->createRegistry(new PermissionCheckingVirtualFilesystem($storage, $this->createSecurityStub())), $this->createItemFactory());
    }

    private function createRegistry(VirtualFilesystemInterface $filesystem): VirtualFilesystemRegistry
    {
        return new VirtualFilesystemRegistry(new ServiceLocator(['files' => static fn () => $filesystem]), ['files' => []]);
    }

    private function createSecurityStub(): Security
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        return $security;
    }

    private function createItemFactory(): VirtualFilesystemItemFactory
    {
        $normalizer = new SchemaAwareObjectNormalizer(new Validator(), [new VirtualFilesystemMetadataNormalizationHandler()]);

        return new VirtualFilesystemItemFactory($normalizer);
    }
}
