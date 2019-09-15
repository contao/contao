<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs;

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\DefaultFileHashProvider;
use Contao\CoreBundle\Filesystem\Storage;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;

class DbafsTest extends TestCase
{
    public function testSyncReportsEmptyChangeIfInSync(): void
    {
        $dbafs = $this->getDbafs($this->getTestFilesystem(), $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $this->assertTrue($changeSet->isEmpty());
    }

    /**
     * @testWith ["foo"]
     *           ["foo/"]
     *           ["foo/bar"]
     *           ["foo/bar/"]
     */
    public function testPartialSyncReportsEmptyChangeSetIfScopeIsInSync(string $scope): void
    {
        $dbafs = $this->getDbafs($this->getTestFilesystemForPartialSync(), $this->mockConnection($this->getTestDatabaseItemsForPartialSync()));
        $changeSet = $dbafs->sync($scope, true);

        $this->assertTrue($changeSet->isEmpty());
    }

    public function testSyncFindsAddedFiles(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->write('baz.txt', 'baz');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $this->assertEmpty($changeSet->getItemsToUpdate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [[
            ChangeSet::ATTRIBUTE_HASH => '73feffa4b7f6bb68e44cf984c85f6e88',
            ChangeSet::ATTRIBUTE_PATH => 'baz.txt',
        ]];

        $this->assertSame($expectedItems, $changeSet->getItemsToCreate());
    }

    /**
     * @testWith ["foo"]
     *           ["foo/"]
     *           ["foo/bar"]
     *           ["foo/bar/"]
     */
    public function testPartialSyncFindsAddedFilesInsideScope(string $scope): void
    {
        $filesystem = $this->getTestFilesystemForPartialSync();
        $filesystem->write('foo/bar/baz3.txt', 'baz');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItemsForPartialSync()));
        $changeSet = $dbafs->sync($scope, true);

        $expectedItemsToCreate = [[
            ChangeSet::ATTRIBUTE_HASH => '73feffa4b7f6bb68e44cf984c85f6e88',
            ChangeSet::ATTRIBUTE_PATH => 'foo/bar/baz3.txt',
        ]];

        $expectedItemsToUpdate = [
            'foo/' => [
                ChangeSet::ATTRIBUTE_HASH => 'aed4211caf92d3900d671a9c8a0a67b4',
            ],
            'foo/bar/' => [
                ChangeSet::ATTRIBUTE_HASH => '88633b438c38c13b2a92d120481bdcbe',
            ],
        ];

        $this->assertEmpty($changeSet->getItemsToDelete());
        $this->assertSame($expectedItemsToCreate, $changeSet->getItemsToCreate());
        $this->assertSame($expectedItemsToUpdate, $changeSet->getItemsToUpdate());
    }

    public function testSyncFindsRemovedFiles(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->delete('bar.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToUpdate());

        $this->assertSame(['bar.txt'], $changeSet->getItemsToDelete());
    }

    /**
     * @testWith ["foo"]
     *           ["foo/"]
     *           ["foo/bar"]
     *           ["foo/bar/"]
     */
    public function testPartialSyncFindsRemovedFilesInsideScope($scope): void
    {
        $filesystem = $this->getTestFilesystemForPartialSync();
        $filesystem->delete('foo/bar/baz1.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItemsForPartialSync()));
        $changeSet = $dbafs->sync($scope, true);

        $expectedItemsToUpdate = [
            'foo/' => [
                ChangeSet::ATTRIBUTE_HASH => 'cbbe62a9c654236c91d3509494764152',
            ],
            'foo/bar/' => [
                ChangeSet::ATTRIBUTE_HASH => '5a21f9e8d3d9a3e6408c4165ee7bae48',
            ],
        ];

        $expectedItemsToDelete = ['foo/bar/baz1.txt'];

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertSame($expectedItemsToUpdate, $changeSet->getItemsToUpdate());
        $this->assertSame($expectedItemsToDelete, $changeSet->getItemsToDelete());
    }

    public function testSyncTracksUpdatedFileContents(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->update('bar.txt', 'new content');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = ['bar.txt' => [
            ChangeSet::ATTRIBUTE_HASH => '96c15c2bb2921193bf290df8cd85e2ba',
        ]];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    /**
     * @testWith ["foo"]
     *           ["foo/"]
     *           ["foo/bar"]
     *           ["foo/bar/"]
     */
    public function testPartialSyncTracksUpdatedFileContentsInsideScope(string $scope): void
    {
        $filesystem = $this->getTestFilesystemForPartialSync();
        $filesystem->update('foo/bar/baz1.txt', 'new content');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItemsForPartialSync()));
        $changeSet = $dbafs->sync($scope, true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [
            'foo/' => [
                ChangeSet::ATTRIBUTE_HASH => '60e68b4505eefd91db955b1fd7175d48',
            ],
            'foo/bar/' => [
                ChangeSet::ATTRIBUTE_HASH => '03bb543156801e4dc53239b2fe0f3886',
            ],
            'foo/bar/baz1.txt' => [
                ChangeSet::ATTRIBUTE_HASH => '96c15c2bb2921193bf290df8cd85e2ba',
            ],
        ];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    public function testSyncTracksMovedFiles(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->rename('bar.txt', 'new.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [
            'bar.txt' => [
                ChangeSet::ATTRIBUTE_PATH => 'new.txt',
            ],
        ];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    /**
     * @testWith ["foo"]
     *           ["foo/"]
     *           ["foo/bar"]
     *           ["foo/bar/"]
     */
    public function testPartialSyncTracksMovedFilesInsideScope(string $scope): void
    {
        $filesystem = $this->getTestFilesystemForPartialSync();
        $filesystem->rename('foo/bar/baz1.txt', 'foo/bar/baz3.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItemsForPartialSync()));
        $changeSet = $dbafs->sync($scope, true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [
            'foo/' => [
                ChangeSet::ATTRIBUTE_HASH => '7148f89a88ab20a8796852ea84c6474e',
            ],
            'foo/bar/' => [
                ChangeSet::ATTRIBUTE_HASH => 'c86cf2d5edf7d589d0faa0403b0bb5b6',
            ],
            'foo/bar/baz1.txt' => [
                ChangeSet::ATTRIBUTE_PATH => 'foo/bar/baz3.txt',
            ],
        ];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    public function testSyncTracksMultipleMovedFilesWithSameHash(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->rename('bar.txt', 'new.txt');
        $filesystem->rename('bar2.txt', 'foo/bar2.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [
            'bar.txt' => [
                ChangeSet::ATTRIBUTE_PATH => 'new.txt',
            ],
            'bar2.txt' => [
                ChangeSet::ATTRIBUTE_PATH => 'foo/bar2.txt',
            ],
            'foo/' => [
                ChangeSet::ATTRIBUTE_HASH => 'd661150fda4a69ee127d14d78b999e71',
            ],
        ];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    public function testSyncTracksDirectoryHashChanges(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->rename('bar.txt', 'foo/new.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [
            'bar.txt' => [
                ChangeSet::ATTRIBUTE_PATH => 'foo/new.txt',
            ],
            'foo/' => [
                ChangeSet::ATTRIBUTE_HASH => 'f17b58dc0e95850136c4a0eb4a834bd2',
            ],
        ];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    public function testSyncFindsAndTracksMultiple(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->rename('bar.txt', 'foo/new.txt');
        $filesystem->delete('foo/file2');
        $filesystem->write('a/b.csv', 'a, b, c');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection($this->getTestDatabaseItems()));
        $changeSet = $dbafs->sync('', true);

        $expectedItemsToCreate = [
            [
                ChangeSet::ATTRIBUTE_HASH => '4d240a387b35ddca963db6deb142e5ab',
                ChangeSet::ATTRIBUTE_PATH => 'a/',
            ],
            [
                ChangeSet::ATTRIBUTE_HASH => '64f47382e7ddc46583bf6d2abedf4140',
                ChangeSet::ATTRIBUTE_PATH => 'a/b.csv',
            ],
        ];

        $expectedItemsToUpdate = [
            'bar.txt' => [
                ChangeSet::ATTRIBUTE_PATH => 'foo/new.txt',
            ],
            'foo/' => [
                ChangeSet::ATTRIBUTE_HASH => '676f68fc601c3f559d19b8f81082192d',
            ],
        ];

        $expectedItemsToDelete = ['foo/file2'];

        $this->assertSame($expectedItemsToCreate, $changeSet->getItemsToCreate());
        $this->assertSame($expectedItemsToUpdate, $changeSet->getItemsToUpdate());
        $this->assertSame($expectedItemsToDelete, $changeSet->getItemsToDelete());
    }

    public function testSyncAppliesChangesIfNotDryRunning(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->write('baz.txt', 'baz');

        $connection = $this->mockConnection($this->getTestDatabaseItems());
        $connection
            ->expects($this->once())
            ->method('insert')
            ->with('tl_files', $this->anything())
            ->willReturnCallback(
                function ($class, $data): void {
                    $this->assertSame('uuidZ', $data['uuid']);
                    $this->assertNull($data['pid']);
                    $this->assertSame('files/baz.txt', $data['path']);
                    $this->assertSame('73feffa4b7f6bb68e44cf984c85f6e88', $data['hash']);
                    $this->assertSame('baz.txt', $data['name']);
                    $this->assertSame('txt', $data['extension']);
                    $this->assertSame('file', $data['type']);
                    $this->assertGreaterThanOrEqual(time(), $data['tstamp']);
                }
            );

        $dbafs = $this->getDbafs($filesystem, $connection);
        $dbafs->setDatabaseBulkInsertSize(0);

        // dry run - should not apply changes
        $changeSet = $dbafs->sync('', true);
        $this->assertFalse($changeSet->isEmpty());

        // regular run - should apply changes
        $dbafs->sync();
    }

    private function getTestFilesystem()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $filesystem->write('foo/file1', 'abc');
        $filesystem->write('foo/file2', 'def');
        $filesystem->write('bar.txt', '123456');
        $filesystem->write('bar2.txt', '123456');

        return $filesystem;
    }

    private function getTestDatabaseItems(): array
    {
        return [
            // path, uuid, hash, isFolder, isIncluded
            ['files/foo/file1', 'uuid1', '900150983cd24fb0d6963f7d28e17f72', 0, 1],
            ['files/foo/file2', 'uuid2', '4ed9407630eb1000c0f6b63842defa7d', 0, 1],
            ['files/foo', 'uuid3', '572960bfcaec10b26b09345ce2873f43', 1, 1],
            ['files/bar.txt', 'uuid4', 'e10adc3949ba59abbe56e057f20f883e', 0, 1],
            ['files/bar2.txt', 'uuid5', 'e10adc3949ba59abbe56e057f20f883e', 0, 1],
        ];
    }

    private function getTestFilesystemForPartialSync()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $filesystem->write('foo/bar/baz1.txt', 'abc');
        $filesystem->write('foo/bar/baz2.txt', 'def');
        $filesystem->write('other/file2', 'def');
        $filesystem->write('bar.txt', '123456');

        return $filesystem;
    }

    private function getTestDatabaseItemsForPartialSync(): array
    {
        return [
            // path, uuid, hash, isFolder, isIncluded
            ['files/foo/bar/baz1.txt', 'uuid1', '900150983cd24fb0d6963f7d28e17f72', 0, 1],
            ['files/foo/bar/baz2.txt', 'uuid2', '4ed9407630eb1000c0f6b63842defa7d', 0, 1],
            ['files/foo/bar', 'uuid3', '2d4a3a2ee46848f9fe2d99aff3287392', 1, 1],
            ['files/foo', 'uuid4', 'f0a47e35f1db321259e0649c0d45f4b1', 1, 1],
            ['files/bar.txt', 'uuid5', 'e10adc3949ba59abbe56e057f20f883e', 0, 0],
            ['files/other/file1', 'uuid6', '900150983cd24fb0d6963f7d28e17f72', 0, 0],
            ['files/other/file2', 'uuid7', '4ed9407630eb1000c0f6b63842defa7d', 0, 0],
            ['files/other', 'uuid8', '185c0d620fe8d51981d49dc99b6001a6', 1, 0],
        ];
    }

    private function mockConnection(array $databaseItems)
    {
        $dataStatement = $this->createMock(Statement::class);
        $dataStatement
            ->method('fetchAll')
            ->willReturn($databaseItems);

        $uuidStatement = $this->createMock(Statement::class);
        $uuidStatement
            ->method('fetchAll')
            ->willReturn(['uuidX', 'uuidY', 'uuidZ']);

        $connection = $this->createMock(Connection::class);

        $connection
            ->expects($this->atLeastOnce())
            ->method('executeQuery')
            ->willReturnCallback(function ($query) use ($uuidStatement, $dataStatement) {
                if (false !== strpos($query, 'LOCK TABLES')) {
                    return null;
                }

                if (false !== strpos($query, 'SELECT path, uuid, hash')) {
                    return $dataStatement;
                }

                if (false !== strpos($query, 'UUID()')) {
                    return $uuidStatement;
                }

                $this->fail('unmatched query');
                return null;
            });

        return $connection;
    }

    private function getDbafs($filesystem, $connection): Dbafs
    {
        return new Dbafs(
            new Storage($filesystem),
            new DefaultFileHashProvider($filesystem),
            $connection,
            'files'
        );
    }
}
