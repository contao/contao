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

use Contao\CoreBundle\Filesystem\Dbafs\DbafsDatabase;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

class DbafsDatabaseTest extends TestCase
{
    public function testGetDatabaseEntriesReturnsAllItems(): void
    {
        $databaseItems = [
            // path, uuid, hash, isFolder, isIncluded
            ['files/foo/bar/baz1.txt', 'uuid1', '900150983cd24fb0d6963f7d28e17f72', 0, 1],
            ['files/foo/bar/baz2.txt', 'uuid2', '4ed9407630eb1000c0f6b63842defa7d', 0, 1],
            ['files/foo/bar', 'uuid3', '2d4a3a2ee46848f9fe2d99aff3287392', 1, 1],
            ['files/foo', 'uuid4', 'f0a47e35f1db321259e0649c0d45f4b1', 1, 1],
            ['files/bar.txt', 'uuid5', 'e10adc3949ba59abbe56e057f20f883e', 0, 1],
            ['files/other/file1', 'uuid6', '900150983cd24fb0d6963f7d28e17f72', 0, 1],
            ['files/other/file2', 'uuid7', '4ed9407630eb1000c0f6b63842defa7d', 0, 1],
            ['files/other', 'uuid8', '185c0d620fe8d51981d49dc99b6001a6', 1, 1],
        ];

        $expectedDbPaths = [
            'bar.txt',
            'foo/',
            'foo/bar/',
            'foo/bar/baz1.txt',
            'foo/bar/baz2.txt',
            'other/',
            'other/file1',
            'other/file2',
        ];

        $expectedHashLookup = [
            'bar.txt' => 'e10adc3949ba59abbe56e057f20f883e',
            'foo/' => 'f0a47e35f1db321259e0649c0d45f4b1',
            'foo/bar/' => '2d4a3a2ee46848f9fe2d99aff3287392',
            'foo/bar/baz1.txt' => '900150983cd24fb0d6963f7d28e17f72',
            'foo/bar/baz2.txt' => '4ed9407630eb1000c0f6b63842defa7d',
            'other/' => '185c0d620fe8d51981d49dc99b6001a6',
            'other/file1' => '900150983cd24fb0d6963f7d28e17f72',
            'other/file2' => '4ed9407630eb1000c0f6b63842defa7d',
        ];

        $expectedUuidLookup = [
            'bar.txt'=> 'uuid5',
            'foo/'=> 'uuid4',
            'foo/bar/'=> 'uuid3',
            'foo/bar/baz1.txt'=> 'uuid1',
            'foo/bar/baz2.txt'=> 'uuid2',
            'other/'=> 'uuid8',
            'other/file1'=> 'uuid6',
            'other/file2'=> 'uuid7',
        ];

        $database = new DbafsDatabase($this->mockConnection($databaseItems), 'files');
        [$dbPaths, $hashLookup, $uuidLookup] = $database->getDatabaseEntries();

        sort($dbPaths);
        ksort($hashLookup);
        ksort($uuidLookup);

        $this->assertEquals($expectedDbPaths, $dbPaths);
        $this->assertEquals($expectedHashLookup, $hashLookup);
        $this->assertEquals($expectedUuidLookup, $uuidLookup);
    }

    /**
     * @testWith ["foo/bar"]
     *           ["foo/bar/"]
     */
    public function testGetDatabaseEntriesReturnsItemsWithScope($scope): void
    {
        $databaseItems = [
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

        $expectedDbPaths = [
            'foo/',
            'foo/bar/',
            'foo/bar/baz1.txt',
            'foo/bar/baz2.txt',
        ];

        $expectedHashLookup = [
            'bar.txt' => 'e10adc3949ba59abbe56e057f20f883e',
            'foo/' => 'f0a47e35f1db321259e0649c0d45f4b1',
            'foo/bar/' => '2d4a3a2ee46848f9fe2d99aff3287392',
            'foo/bar/baz1.txt' => '900150983cd24fb0d6963f7d28e17f72',
            'foo/bar/baz2.txt' => '4ed9407630eb1000c0f6b63842defa7d',
            'other/' => '185c0d620fe8d51981d49dc99b6001a6',
            'other/file1' => '900150983cd24fb0d6963f7d28e17f72',
            'other/file2' => '4ed9407630eb1000c0f6b63842defa7d',
        ];

        $expectedUuidLookup = [
            'bar.txt'=> 'uuid5',
            'foo/'=> 'uuid4',
            'foo/bar/'=> 'uuid3',
            'foo/bar/baz1.txt'=> 'uuid1',
            'foo/bar/baz2.txt'=> 'uuid2',
            'other/'=> 'uuid8',
            'other/file1'=> 'uuid6',
            'other/file2'=> 'uuid7',
        ];

        $database = new DbafsDatabase($this->mockConnection($databaseItems), 'files');
        [$dbPaths, $hashLookup, $uuidLookup] = $database->getDatabaseEntries($scope);

        sort($dbPaths);
        ksort($hashLookup);
        ksort($uuidLookup);

        $this->assertEquals($expectedDbPaths, $dbPaths);
        $this->assertEquals($expectedHashLookup, $hashLookup);
        $this->assertEquals($expectedUuidLookup, $uuidLookup);
    }

//    public function testApplyDatabaseChangesPerformsUpdates(): void
//    {
//        // todo
//    }
//
//    public function testApplyDatabaseChangesPerformsSingleInserts(): void
//    {
//        // todo
//    }
//
//    public function testApplyDatabaseChangesPerformsBulkInserts(): void
//    {
//        // todo
//    }
//
//    public function testApplyDatabaseChangesPerformsDeletes(): void
//    {
//        // todo
//    }
//
//    public function testBeginTransactionAddsLock(): void
//    {
//        // todo
//    }
//
//    public function testCommitTransactionReleasesLock(): void
//    {
//        // todo
//    }

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
}
