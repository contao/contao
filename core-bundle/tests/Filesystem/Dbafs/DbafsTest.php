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
    public function testSyncReportsEmptyChangeSetIfStructuresAreInSync(): void
    {
        $dbafs = $this->getDbafs($this->getTestFilesystem(), $this->mockConnection());
        $changeSet = $dbafs->sync(true);

        $this->assertTrue($changeSet->isEmpty());
    }

    public function testSyncFindsAddedFiles(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->write('baz.txt', 'baz');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection());
        $changeSet = $dbafs->sync(true);

        $this->assertEmpty($changeSet->getItemsToUpdate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [[
            ChangeSet::ATTRIBUTE_HASH => '73feffa4b7f6bb68e44cf984c85f6e88',
            ChangeSet::ATTRIBUTE_PATH => 'baz.txt',
        ]];

        $this->assertSame($expectedItems, $changeSet->getItemsToCreate());
    }

    public function testSyncFindsRemovedFiles(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->delete('bar.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection());
        $changeSet = $dbafs->sync(true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToUpdate());

        $this->assertSame(['bar.txt'], $changeSet->getItemsToDelete());
    }

    public function testSyncTracksUpdatedFileContents(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->update('bar.txt', 'new content');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection());
        $changeSet = $dbafs->sync(true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = ['bar.txt' => [
            ChangeSet::ATTRIBUTE_HASH => '96c15c2bb2921193bf290df8cd85e2ba',
        ]];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    public function testSyncTracksMovedFiles(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->rename('bar.txt', 'new.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection());
        $changeSet = $dbafs->sync(true);

        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToDelete());

        $expectedItems = [
            'bar.txt' => [
                ChangeSet::ATTRIBUTE_PATH => 'new.txt',
            ],
        ];

        $this->assertSame($expectedItems, $changeSet->getItemsToUpdate());
    }

    public function testSyncTracksMultipleMovedFilesWithSameHash(): void
    {
        $filesystem = $this->getTestFilesystem();
        $filesystem->rename('bar.txt', 'new.txt');
        $filesystem->rename('bar2.txt', 'foo/bar2.txt');

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection());
        $changeSet = $dbafs->sync(true);

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

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection());
        $changeSet = $dbafs->sync(true);

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

        $dbafs = $this->getDbafs($filesystem, $this->mockConnection());
        $changeSet = $dbafs->sync(true);

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

//    public function testSyncAppliesDatabaseChanges()
//    {
//        // todo
//    }
//
//    public function testSyncDoesNotApplyChangesInDryRun()
//    {
//        // todo
//    }

    private function getTestFilesystem()
    {
        $filesystem = new Filesystem(new MemoryAdapter());

        $filesystem->write('foo/file1', 'abc');
        $filesystem->write('foo/file2', 'def');
        $filesystem->write('bar.txt', '123456');
        $filesystem->write('bar2.txt', '123456');

        return $filesystem;
    }

    private function mockConnection(): Connection
    {
        $databaseItems = [
            ['files/foo/file1', 'uuid1', '900150983cd24fb0d6963f7d28e17f72', 0],
            ['files/foo/file2', 'uuid2', '4ed9407630eb1000c0f6b63842defa7d', 0],
            ['files/foo', 'uuid3', '572960bfcaec10b26b09345ce2873f43', 1],
            ['files/bar.txt', 'uuid4', 'e10adc3949ba59abbe56e057f20f883e', 0],
            ['files/bar2.txt', 'uuid5', 'e10adc3949ba59abbe56e057f20f883e', 0],
        ];

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn($databaseItems)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('executeQuery')
            ->willReturn($statement)
        ;

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
