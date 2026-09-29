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
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\File\TextTrack;
use Contao\CoreBundle\File\TextTrackType;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\Image\ImportantPart;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

final class VirtualFilesystemStateProviderTest extends TestCase
{
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

        $result = new VirtualFilesystemStateProvider($storage, $this->createSecurityStub(), $this->createItemFactory())->provide(new Get(), ['path' => 'images/example.jpg']);

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

        $result = new VirtualFilesystemStateProvider($storage, $this->createSecurityStub(), $this->createItemFactory())->provide(
            new GetCollection(),
            context: ['filters' => ['path' => 'documents', 'deep' => true]],
        );

        $this->assertCount(2, $result);
        $this->assertSame('documents/Guide.pdf', $result[0]->path);
        $this->assertSame([], $result[0]->metadata);
        $this->assertSame('documents/notes.txt', $result[1]->path);
    }

    public function testReturnsNotFoundForAMissingItem(): void
    {
        $storage = $this->createStub(VirtualFilesystem::class);

        $this->expectException(NotFoundHttpException::class);
        new VirtualFilesystemStateProvider($storage, $this->createSecurityStub(), $this->createItemFactory())->provide(new Get(), ['path' => 'missing.txt']);
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
