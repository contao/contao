<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\File\MetadataBag;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Symfony\Component\Uid\Uuid;

class FilesystemItemTest extends TestCase
{
    public function testSetAndGetAttributes(): void
    {
        $uuid = Uuid::fromString('2fcae369-c955-4b43-bcf9-d069f9d25542');

        $fileItem = new FilesystemItem(
            true,
            'foo/bar.PNG',
            123450,
            1024,
            'image/png',
            ['foo' => 'bar', 'uuid' => $uuid],
        );

        $this->assertTrue($fileItem->isFile());
        $this->assertSame('foo/bar.PNG', $fileItem->getPath());
        $this->assertSame('foo/bar.PNG', (string) $fileItem);
        $this->assertSame(123450, $fileItem->getLastModified());
        $this->assertSame(1024, $fileItem->getFileSize());
        $this->assertSame('image/png', $fileItem->getMimeType());
        $this->assertSame('PNG', $fileItem->getExtension());
        $this->assertSame('png', $fileItem->getExtension(true));
        $this->assertSame('bar.PNG', $fileItem->getName());
        $this->assertSame('bar', $fileItem->getExtraMetadata()['foo']);
        $this->assertSame('2fcae369-c955-4b43-bcf9-d069f9d25542', $fileItem->getUuid()->toRfc4122());
    }

    public function testTypeHelperShortcuts(): void
    {
        $fileItem = new FilesystemItem(true, 'foo/bar.png', 123450, 1024, 'image/png');
        $this->assertTrue($fileItem->isImage());
        $this->assertFalse($fileItem->isAudio());
        $this->assertFalse($fileItem->isVideo());
        $this->assertFalse($fileItem->isPdf());
        $this->assertFalse($fileItem->isSpreadsheet());

        $fileItem = new FilesystemItem(true, 'foo/bar.mp4', 123450, 1024, 'video/mp4');
        $this->assertFalse($fileItem->isImage());
        $this->assertFalse($fileItem->isAudio());
        $this->assertTrue($fileItem->isVideo());
        $this->assertFalse($fileItem->isPdf());
        $this->assertFalse($fileItem->isSpreadsheet());

        $fileItem = new FilesystemItem(true, 'foo/bar.m4a', 123450, 1024, 'audio/mp4');
        $this->assertFalse($fileItem->isImage());
        $this->assertTrue($fileItem->isAudio());
        $this->assertFalse($fileItem->isVideo());
        $this->assertFalse($fileItem->isPdf());
        $this->assertFalse($fileItem->isSpreadsheet());

        $fileItem = new FilesystemItem(true, 'foo/bar.pdf', 123450, 1024, 'application/pdf');
        $this->assertFalse($fileItem->isImage());
        $this->assertFalse($fileItem->isAudio());
        $this->assertFalse($fileItem->isVideo());
        $this->assertTrue($fileItem->isPdf());
        $this->assertFalse($fileItem->isSpreadsheet());

        $fileItem = new FilesystemItem(true, 'foo/bar.xlsx', 123450, 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertFalse($fileItem->isImage());
        $this->assertFalse($fileItem->isAudio());
        $this->assertFalse($fileItem->isVideo());
        $this->assertFalse($fileItem->isPdf());
        $this->assertTrue($fileItem->isSpreadsheet());

        $fileItem = new FilesystemItem(false, '/path/to/folder');
        $this->assertFalse($fileItem->isImage());
        $this->assertFalse($fileItem->isAudio());
        $this->assertFalse($fileItem->isVideo());
        $this->assertFalse($fileItem->isPdf());
        $this->assertFalse($fileItem->isSpreadsheet());
    }

    /**
     * @dataProvider provideSchemaOrgData
     */
    public function testGettingSchemaOrgData(string $path, string $mimeType, array $expectedSchema): void
    {
        $fileItem = new FilesystemItem(
            true,
            $path,
            123450,
            1024,
            $mimeType,
            [
                'uuid' => Uuid::fromString('2fcae369-c955-4b43-bcf9-d069f9d25542'),
                'metadata' => new MetadataBag(
                    [
                        'en' => new Metadata([
                            Metadata::VALUE_TITLE => 'My title!',
                        ]),
                    ],
                    ['en'],
                ),
            ],
        );

        $this->assertSame($expectedSchema, $fileItem->getSchemaOrgData());
    }

    /**
     * @dataProvider provideProperties
     */
    public function testPreventAccessingFileAttributesOnDirectories(string $property, string $exception): void
    {
        $item = new FilesystemItem(false, 'foo/bar', 0);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($exception);

        $item->$property();
    }

    public function provideProperties(): \Generator
    {
        yield 'file size' => [
            'getFileSize',
            'Cannot call getFileSize() on a non-file filesystem item.',
        ];

        yield 'mime type' => [
            'getMimeType',
            'Cannot call getMimeType() on a non-file filesystem item.',
        ];
    }

    public function testGetLazy(): void
    {
        $invocationCounts = [
            'lastModified' => 0,
            'fileSize' => 0,
            'mimeType' => 0,
            'extraMetadata' => 0,
        ];

        $fileItem = new FilesystemItem(
            true,
            'foo/bar.png',
            static function () use (&$invocationCounts): int {
                ++$invocationCounts['lastModified'];

                return 123450;
            },
            static function () use (&$invocationCounts): int {
                ++$invocationCounts['fileSize'];

                return 1024;
            },
            static function () use (&$invocationCounts): string {
                ++$invocationCounts['mimeType'];

                return 'image/png';
            },
            static function () use (&$invocationCounts): array {
                ++$invocationCounts['extraMetadata'];

                return ['foo' => 'bar'];
            },
        );

        $this->assertTrue($fileItem->isFile());
        $this->assertSame('foo/bar.png', $fileItem->getPath());

        // Accessing multiple times should cache the result
        for ($i = 0; $i < 2; ++$i) {
            $this->assertSame(123450, $fileItem->getLastModified());
            $this->assertSame(1024, $fileItem->getFileSize());
            $this->assertSame('image/png', $fileItem->getMimeType());
            $this->assertSame(['foo' => 'bar'], $fileItem->getExtraMetadata());
        }

        foreach ($invocationCounts as $property => $invocationCount) {
            $this->assertSame(1, $invocationCount, "invocation count of $property()");
        }
    }

    public function testCreateFromStorageAttributes(): void
    {
        $fileAttributes = new FileAttributes(
            'foo/bar.png',
            1024,
            null,
            123450,
            'image/png',
            ['foo' => 'bar'],
        );

        $fileItem = FilesystemItem::fromStorageAttributes($fileAttributes);

        $this->assertTrue($fileItem->isFile());
        $this->assertSame('foo/bar.png', $fileItem->getPath());
        $this->assertSame(123450, $fileItem->getLastModified());
        $this->assertSame(1024, $fileItem->getFileSize());
        $this->assertSame('image/png', $fileItem->getMimeType());
        $this->assertSame(['foo' => 'bar'], $fileItem->getExtraMetadata());

        $directoryAttributes = new DirectoryAttributes(
            'foo/bar',
            null,
            123450,
        );

        $directoryItem = FilesystemItem::fromStorageAttributes($directoryAttributes);

        $this->assertFalse($directoryItem->isFile());
        $this->assertSame('foo/bar', $directoryItem->getPath());
        $this->assertSame(123450, $directoryItem->getLastModified());
    }

    public function testWithMetadataIfNotDefined(): void
    {
        $item = new FilesystemItem(true, 'some/path');

        $this->assertNull($item->getLastModified());
        $this->assertSame(0, $item->getFileSize());
        $this->assertSame('', $item->getMimeType(''));

        $invocationCounts = [
            'lastModified' => 0,
            'fileSize' => 0,
            'mimeType' => 0,
        ];

        $item = $item->withMetadataIfNotDefined(
            static function () use (&$invocationCounts): int {
                ++$invocationCounts['lastModified'];

                return 123450;
            },
            static function () use (&$invocationCounts): int {
                ++$invocationCounts['fileSize'];

                return 1024;
            },
            static function () use (&$invocationCounts): string {
                ++$invocationCounts['mimeType'];

                return 'image/png';
            },
        );

        // Accessing multiple times should cache the result
        for ($i = 0; $i < 2; ++$i) {
            $this->assertSame(123450, $item->getLastModified());
            $this->assertSame(1024, $item->getFileSize());
            $this->assertSame('image/png', $item->getMimeType());
        }

        foreach ($invocationCounts as $property => $invocationCount) {
            $this->assertSame(1, $invocationCount, "invocation count of $property()");
        }
    }

    public function testThrowsIfMimeTypeIsNotDefined(): void
    {
        $item = new FilesystemItem(
            true,
            'some/file.txt',
            null,
            null,
            static function (): never {
                throw VirtualFilesystemException::unableToRetrieveMetadata('some/file.txt');
            },
        );

        $this->expectException(VirtualFilesystemException::class);
        $this->expectExceptionMessage('Unable to retrieve metadata from "some/file.txt": A mime type could not be detected. Set the "$default" argument to suppress this exception.');

        $item->getMimeType();
    }

    public function testReturnsDefaultMimeTypeIfSpecified(): void
    {
        $item = new FilesystemItem(
            true,
            'some/file.txt',
            null,
            null,
            static function (): never {
                throw VirtualFilesystemException::unableToRetrieveMetadata('some/file.txt');
            },
        );

        $this->assertSame('text/plain', $item->getMimeType('text/plain'));
    }

    public function testWithMetadataIfNotDefinedDoesNotOverwriteExistingValues(): void
    {
        $item = new FilesystemItem(true, 'some/path', 123450, static fn () => 1024, 'image/png');
        $item = $item->withMetadataIfNotDefined(static fn () => 98765, 2048, null);

        $this->assertSame(123450, $item->getLastModified());
        $this->assertSame(1024, $item->getFileSize());
        $this->assertSame('image/png', $item->getMimeType());
    }

    public function provideSchemaOrgData(): \Generator
    {
        yield 'Test an image' => [
            'foo/bar.png',
            'image/png',
            [
                '@type' => 'ImageObject',
                'contentUrl' => 'foo/bar.png',
                'encodingFormat' => 'image/png',
                'identifier' => '#/schema/file/2fcae369-c955-4b43-bcf9-d069f9d25542',
                'name' => 'My title!',
            ],
        ];

        yield 'Test a video' => [
            'foo/bar.mp4',
            'video/mp4',
            [
                '@type' => 'VideoObject',
                'contentUrl' => 'foo/bar.mp4',
                'encodingFormat' => 'video/mp4',
                'identifier' => '#/schema/file/2fcae369-c955-4b43-bcf9-d069f9d25542',
                'name' => 'My title!',
            ],
        ];

        yield 'Test an audio file' => [
            'foo/bar.m4a',
            'audio/mp4',
            [
                '@type' => 'AudioObject',
                'contentUrl' => 'foo/bar.m4a',
                'encodingFormat' => 'audio/mp4',
                'identifier' => '#/schema/file/2fcae369-c955-4b43-bcf9-d069f9d25542',
                'name' => 'My title!',
            ],
        ];

        yield 'Test a pdf file' => [
            'foo/bar.pdf',
            'application/pdf',
            [
                '@type' => 'DigitalDocument',
                'contentUrl' => 'foo/bar.pdf',
                'encodingFormat' => 'application/pdf',
                'identifier' => '#/schema/file/2fcae369-c955-4b43-bcf9-d069f9d25542',
                'name' => 'My title!',
            ],
        ];

        yield 'Test a spreadsheet file' => [
            'foo/bar.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            [
                '@type' => 'SpreadsheetDigitalDocument',
                'contentUrl' => 'foo/bar.xlsx',
                'encodingFormat' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'identifier' => '#/schema/file/2fcae369-c955-4b43-bcf9-d069f9d25542',
                'name' => 'My title!',
            ],
        ];

        yield 'Test file without special handling is a regular MediaObject' => [
            'foo/bar.sql',
            'application/sql',
            [
                '@type' => 'MediaObject',
                'contentUrl' => 'foo/bar.sql',
                'encodingFormat' => 'application/sql',
                'identifier' => '#/schema/file/2fcae369-c955-4b43-bcf9-d069f9d25542',
                'name' => 'My title!',
            ],
        ];
    }
}
