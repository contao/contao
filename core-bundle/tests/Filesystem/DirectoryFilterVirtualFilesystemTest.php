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

use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\DirectoryFilterVirtualFilesystem;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemException;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\Attributes\DataProvider;

class DirectoryFilterVirtualFilesystemTest extends TestCase
{
    public function testFiltersStorage(): void
    {
        $mountManager = new MountManager([]);
        $mountManager
            ->mount(new InMemoryFilesystemAdapter(), '')
        ;

        $baseStorage = new VirtualFilesystem(
            $mountManager,
            $this->createMock(DbafsManager::class),
            '',
        );

        $baseStorage->createDirectory('images');
        $baseStorage->createDirectory('images/photos');
        $baseStorage->createDirectory('images/photos/foo');
        $baseStorage->createDirectory('images/graphics');
        $baseStorage->createDirectory('private');
        $baseStorage->createDirectory('private/documents');
        $baseStorage->createDirectory('private/documents/important');
        $baseStorage->createDirectory('secret');

        $baseStorage->write('root-file', 'root-file');
        $baseStorage->write('images/i', 'i');
        $baseStorage->write('images/photos/p1', 'p1');
        $baseStorage->write('images/photos/foo/p2', 'p2');
        $baseStorage->write('images/graphics/g1', 'g1');
        $baseStorage->write('private/documents/d1', 'd1');
        $baseStorage->write('private/documents/important/d2', 'd2');
        $baseStorage->write('secret/s', 's');

        $this->assertSame(
            [
                'images',
                'images/photos',
                'images/photos/foo',
                'images/photos/foo/p2',
                'images/photos/p1',
                'images/graphics',
                'images/graphics/g1',
                'images/i',
                'private',
                'private/documents',
                'private/documents/important',
                'private/documents/important/d2',
                'private/documents/d1',
                'secret',
                'secret/s',
                'root-file',
            ],
            $this->getListingAsArray($baseStorage),
        );

        $filterStorage = new DirectoryFilterVirtualFilesystem($baseStorage, [
            'images/photos',
            'images/photos/foo',
            'private/documents',
            'random',
        ]);

        $this->assertSame(
            [
                'images',
                'images/photos',
                'images/photos/foo',
                'images/photos/foo/p2',
                'images/photos/p1',
                'private',
                'private/documents',
                'private/documents/important',
                'private/documents/important/d2',
                'private/documents/d1',
                'random',
            ],
            $this->getListingAsArray($filterStorage),
        );

        // Test that anything outside the filter scope does not exist
        $this->assertFalse($filterStorage->directoryExists('secret'));
        $this->assertFalse($filterStorage->fileExists('root-file'));
        $this->assertFalse($filterStorage->fileExists('secret/s'));
        $this->assertFalse($filterStorage->has('root-file'));
        $this->assertFalse($filterStorage->has('secret'));

        // Test that anything inside the allowed paths can be accessed
        $this->assertTrue($filterStorage->directoryExists('private/documents'));
        $this->assertTrue($filterStorage->fileExists('private/documents/important/d2'));
        $this->assertTrue($filterStorage->has('images/photos'));
        $this->assertTrue($filterStorage->get('images/photos/foo/p2')?->isFile());
        $this->assertSame('p2', $filterStorage->read('images/photos/foo/p2'));

        // Test virtual directories/root trail can be accessed
        $this->assertTrue($filterStorage->directoryExists('random'));
        $this->assertEmpty($filterStorage->get('random')?->getExtraMetadata()->all());
        $this->assertNull($filterStorage->get('random')->getLastModified());
        $this->assertTrue($filterStorage->directoryExists('private'));
        $this->assertEmpty($filterStorage->get('private')?->getExtraMetadata()->all());
        $this->assertNull($filterStorage->get('private')->getLastModified());
        $this->assertEmpty($filterStorage->get('')?->getExtraMetadata()->all());
        $this->assertNull($filterStorage->get('')->getLastModified());

        // Test inspection
        $this->assertSame('images/photos', $filterStorage->getFirstNonVirtualDirectory());

        // Test writing to the storage
        $filterStorage->write('private/documents/list', 'list');
        $filterStorage->write('random/new', 'new');
        $filterStorage->createDirectory('random/foo');
        $filterStorage->write('random/foo/thing', 'thing');

        $this->assertTrue($filterStorage->fileExists('random/new'));
        $this->assertTrue($filterStorage->directoryExists('random/foo'));
        $this->assertSame('list', $filterStorage->read('private/documents/list'));
        $this->assertSame('thing', $filterStorage->read('random/foo/thing'));
    }

    #[DataProvider('provideIllegalOperations')]
    public function testDeniesAccess(string $operation, array $arguments, string $expectedExceptionMessage): void
    {
        $filterStorage = new DirectoryFilterVirtualFilesystem($this->createMock(VirtualFilesystemInterface::class), [
            'foo',
            'bar/baz',
        ]);

        $this->expectException(VirtualFilesystemException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $filterStorage->$operation(...$arguments);
    }

    public static function provideIllegalOperations(): \Generator
    {
        yield 'create file in root' => [
            'write', ['file', ''],
            'Unable to write to "file".',
        ];

        yield 'create file in trail' => [
            'write', ['bar/file', ''],
            'Unable to write to "bar/file".',
        ];

        yield 'create directory in root' => [
            'createDirectory', ['directory'],
            'Unable to create directory at "directory".',
        ];

        yield 'create directory in trail' => [
            'createDirectory', ['bar/directory'],
            'Unable to create directory at "bar/directory".',
        ];

        yield 'delete directory in root' => [
            'deleteDirectory', ['bar'],
            'Unable to delete directory at "bar".',
        ];

        yield 'delete allowed directory in root' => [
            'deleteDirectory', ['foo'],
            'Unable to delete directory at "foo".',
        ];

        yield 'delete allowed directory in trail' => [
            'deleteDirectory', ['bar/baz'],
            'Unable to delete directory at "bar/baz".',
        ];

        yield 'copy to path in trail' => [
            'copy', ['bar/baz/x', 'bar/x'],
            'Unable to copy file from "bar/baz/x" to "bar/x".',
        ];

        yield 'copy to arbitrary uncovered path' => [
            'copy', ['bar/baz/x', 'somewhere'],
            'Unable to copy file from "bar/baz/x" to "somewhere".',
        ];

        yield 'move to path in trail' => [
            'move', ['bar/baz/x', 'bar/x'],
            'Unable to move file from "bar/baz/x" to "bar/x".',
        ];

        yield 'move to arbitrary uncovered path' => [
            'move', ['bar/baz/x', 'somewhere'],
            'Unable to move file from "bar/baz/x" to "somewhere".',
        ];

        yield 'get last modified from uncovered path' => [
            'getLastModified', ['somewhere'],
            'Unable to retrieve metadata from "somewhere".',
        ];

        yield 'get filesize from uncovered path' => [
            'getFilesize', ['somewhere'],
            'Unable to retrieve metadata from "somewhere".',
        ];

        yield 'get mime type from uncovered path' => [
            'getMimeType', ['somewhere'],
            'Unable to retrieve metadata from "somewhere".',
        ];

        yield 'get extra metadata from uncovered path' => [
            'getExtraMetadata', ['somewhere'],
            'Unable to retrieve metadata from "somewhere".',
        ];
    }

    /**
     * @return list<string>
     */
    private function getListingAsArray(VirtualFilesystemInterface $storage): array
    {
        return array_map(
            static fn (FilesystemItem $item): string => $item->getPath(),
            $storage->listContents('', true)->toArray(),
        );
    }
}
