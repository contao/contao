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
            'foo/bar.png',
            123450,
            1024,
            'image/png',
            ['foo' => 'bar', 'uuid' => $uuid]
        );

        $this->assertTrue($fileItem->isFile());
        $this->assertSame('foo/bar.png', $fileItem->getPath());
        $this->assertSame('foo/bar.png', (string) $fileItem);
        $this->assertSame(123450, $fileItem->getLastModified());
        $this->assertSame(1024, $fileItem->getFileSize());
        $this->assertSame('image/png', $fileItem->getMimeType());
        $this->assertSame('bar', $fileItem->getExtraMetadata()['foo']);
        $this->assertSame('2fcae369-c955-4b43-bcf9-d069f9d25542', $fileItem->getUuid()->toRfc4122());
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
            ['foo' => 'bar']
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
            123450
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
        $this->assertSame('', $item->getMimeType(false));

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
            static function (): void {
                throw VirtualFilesystemException::unableToRetrieveMetadata('some/file.txt');
            }
        );

        $this->expectException(VirtualFilesystemException::class);
        $this->expectExceptionMessage('Unable to retrieve metadata from "some/file.txt": A mime type could not be detected. Set "$throwOnError" to false to suppress the exception and get an empty string instead.');

        $item->getMimeType();
    }

    public function testWithMetadataIfNotDefinedDoesNotOverwriteExistingValues(): void
    {
        $item = new FilesystemItem(true, 'some/path', 123450, static fn () => 1024, 'image/png');
        $item = $item->withMetadataIfNotDefined(static fn () => 98765, 2048, null);

        $this->assertSame(123450, $item->getLastModified());
        $this->assertSame(1024, $item->getFileSize());
        $this->assertSame('image/png', $item->getMimeType());
    }
}
