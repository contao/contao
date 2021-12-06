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
use Contao\CoreBundle\Filesystem\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ImportantPart;
use Doctrine\DBAL\Connection;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\MockObject\MockObject;

class DbafsTest extends TestCase
{
    public function testResolvePaths(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturnMap([
                [
                    'SELECT * FROM tl_files WHERE id=?', [1], [],
                    ['id' => 1, 'uuid' => 'b33f', 'path' => 'foo/bar1', 'type' => 'file'],
                ],
                [
                    'SELECT * FROM tl_files WHERE uuid=?', ['7a56'], [],
                    ['id' => 2, 'uuid' => '7a56', 'path' => 'foo/bar2', 'type' => 'file'],
                ],
            ])
        ;

        $dbafs = $this->getDbafs($connection);

        $this->assertSame('foo/bar1', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar2', $dbafs->getPathFromUuid('7a56'));

        // Subsequent calls (no matter which identifier) should be served from cache
        $this->assertSame('foo/bar1', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar1', $dbafs->getPathFromUuid('b33f'));
        $this->assertSame('foo/bar1', $dbafs->getRecord('foo/bar1')['path']);
        $this->assertSame('foo/bar2', $dbafs->getPathFromId(2));
    }

    public function testResolvePathsForUnknownRecords(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $dbafs = $this->getDbafs($connection);

        $this->assertNull($dbafs->getPathFromId(1));
        $this->assertNull($dbafs->getPathFromUuid('7a56'));

        // Subsequent calls should be short-circuited
        $this->assertNull($dbafs->getPathFromId(1));
        $this->assertNull($dbafs->getPathFromUuid('7a56'));
    }

    public function testGetRecord(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE path=?', ['foo/bar'], [])
            ->willReturn([
                'id' => 1,
                'uuid' => '9e41',
                'path' => 'foo/bar',
                'type' => 'file',
                'importantPartX' => 0.1,
                'importantPartY' => 0.2,
                'importantPartWidth' => 0.3,
                'importantPartHeight' => 0.4,
                'meta' => serialize([
                    'de' => [Metadata::VALUE_TITLE => 'my title'],
                ]),
            ])
        ;

        $dbafs = $this->getDbafs($connection);

        $record = $dbafs->getRecord('foo/bar');

        $this->assertNotNull($record);
        $this->assertSame('foo/bar', $record['path']);
        $this->assertTrue($record['isFile']);

        $importantPart = $record['extra']['importantPart'];
        $this->assertInstanceOf(ImportantPart::class, $importantPart);
        $this->assertSame(0.1, $importantPart->getX());
        $this->assertSame(0.2, $importantPart->getY());
        $this->assertSame(0.3, $importantPart->getWidth());
        $this->assertSame(0.4, $importantPart->getHeight());

        $metadata = $record['extra']['metadata']['de'];
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertSame('my title', $metadata->getTitle());
        $this->assertSame('9e41', $metadata->getUuid());
    }

    public function testGetUnknownRecord(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $dbafs = $this->getDbafs($connection);

        $this->assertNull($dbafs->getRecord('foo/bar'));

        // Subsequent calls should be short-circuited
        $this->assertNull($dbafs->getRecord('foo/bar'));
    }

    public function testGetMultipleRecords(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->with(
                'SELECT * FROM tl_files WHERE path LIKE ? AND path NOT LIKE ? ORDER BY path',
                ['foo/%', 'foo/%/%'],
                []
            )
            ->willReturn([
                ['id' => 1, 'uuid' => 'b33f', 'path' => 'foo/first', 'type' => 'file'],
                ['id' => 2, 'uuid' => 'a451', 'path' => 'foo/second', 'type' => 'file'],
                ['id' => 3, 'uuid' => 'd98c', 'path' => 'foo/bar', 'type' => 'folder'],
            ])
        ;

        $dbafs = $this->getDbafs($connection);

        $records = iterator_to_array($dbafs->getRecords('foo'));

        $this->assertCount(3, $records);
        [$record1, $record2, $record3] = $records;

        $this->assertSame('foo/first', $record1['path']);
        $this->assertTrue($record1['isFile']);

        $this->assertSame('foo/second', $record2['path']);
        $this->assertTrue($record2['isFile']);

        $this->assertSame('foo/bar', $record3['path']);
        $this->assertFalse($record3['isFile']);
    }

    public function testGetMultipleRecordsNested(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->with(
                'SELECT * FROM tl_files WHERE path LIKE ? ORDER BY path',
                ['foo/%'],
                []
            )
            ->willReturn([
                ['id' => 1, 'uuid' => 'b33f', 'path' => 'foo/first', 'type' => 'file'],
                ['id' => 2, 'uuid' => 'a451', 'path' => 'foo/second', 'type' => 'file'],
                ['id' => 3, 'uuid' => 'd98c', 'path' => 'foo/bar', 'type' => 'folder'],
                ['id' => 4, 'uuid' => 'd98c', 'path' => 'foo/bar/third', 'type' => 'file'],
            ])
        ;

        $dbafs = $this->getDbafs($connection);

        $records = iterator_to_array($dbafs->getRecords('foo', true));

        $this->assertCount(4, $records);
        [$record1, $record2, $record3, $record4] = $records;

        $this->assertSame('foo/first', $record1['path']);
        $this->assertTrue($record1['isFile']);

        $this->assertSame('foo/second', $record2['path']);
        $this->assertTrue($record2['isFile']);

        $this->assertSame('foo/bar', $record3['path']);
        $this->assertFalse($record3['isFile']);

        $this->assertSame('foo/bar/third', $record4['path']);
        $this->assertTrue($record4['isFile']);
    }

    public function testNormalizesPathsIfDatabasePrefixWasSet(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE path=?', ['files/foo/bar'], [])
            ->willReturn(['id' => 1, 'uuid' => 'b33f', 'path' => 'files/foo/bar', 'type' => 'file'])
        ;

        $dbafs = $this->getDbafs($connection);
        $dbafs->setDatabasePathPrefix('files');

        $record = $dbafs->getRecord('foo/bar');
        $this->assertNotNull($record);
        $this->assertSame('foo/bar', $record['path']);

        $this->assertSame('foo/bar', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar', $dbafs->getPathFromUuid('b33f'));
    }

    public function testResetInternalCache(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE id=?', [1], [])
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'uuid' => 'b33f', 'path' => 'foo/bar', 'type' => 'file'],
                ['id' => 1, 'uuid' => '51d2', 'path' => 'other/path', 'type' => 'file']
            )
        ;

        $dbafs = $this->getDbafs($connection);

        $this->assertSame('foo/bar', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar', $dbafs->getPathFromUuid('b33f'));
        $this->assertSame('foo/bar', $dbafs->getRecord('foo/bar')['path']);

        $dbafs->reset();

        $this->assertSame('other/path', $dbafs->getPathFromId(1));
        $this->assertSame('other/path', $dbafs->getPathFromUuid('51d2'));
        $this->assertSame('other/path', $dbafs->getRecord('other/path')['path']);
    }

    /**
     * @dataProvider provideFilesystemsAndExpectedChangeSets
     *
     * @param string|array<int, string> $scope
     */
    public function testComputeChangeSet(FilesystemAdapter $filesystem, $scope, ChangeSet $expected): void
    {
        /*
         * Demo file structure present in the database:
         *
         *  <root>
         *    |-file1
         *    |-file2
         *    |-empty-dir
         *    |  |- ~
         *    |-foo
         *    |  |-file3
         *    |  |-baz
         *    |    |-file4
         *    |-bar
         *       |-file5a
         *       |-file5b
         *
         * File 5a and 5b have the same hash (e.g. both empty files).
         */

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllNumeric')
            ->with("SELECT path, uuid, hash, IF(type='folder', 1, 0) AS is_dir FROM tl_files", [], [])
            ->willReturn([
                ['file1', 'ab54', 'af17bc3b4a86a96a0f053a7e5f7c18ba', 0],
                ['file2', 'df5q', 'ab86a1e1ef70dff97959067b723c5c24', 0],
                ['empty-dir', '5681', 'd41d8cd98f00b204e9800998ecf8427e', 1],
                ['foo', 'ee61', '48a6bbe07d25733e37e2c949ee412d5d', 1],
                ['foo/file3', '80b0', 'ead99c2fbd1b40a59695567afb14c26c', 0],
                ['foo/baz', '238a', '1ef7bcc6fe73d58905d2c8d21853663e', 1],
                ['foo/baz/file4', 'e7a1', '6d4db5ff0c117864a02827bad3c361b9', 0],
                ['bar', '61ff', '06a182c81a4f9c208a44b66fbb3c1d9f', 1],
                ['bar/file5a', '25cd', 'd41d8cd98f00b204e9800998ecf8427e', 0],
                ['bar/file5b', '9f2a', 'd41d8cd98f00b204e9800998ecf8427e', 0],
            ])
        ;

        $dbafs = $this->getDbafs($connection);

        // Lower max file size, so that we can test the limit without excessive memory usage
        $dbafs->setMaxFileSize(100);

        $changeSet = $dbafs->computeChangeSet($filesystem, ...((array) $scope));

        $this->assertSame(
            $expected->getItemsToCreate(),
            $changeSet->getItemsToCreate(),
            'items to create'
        );

        $this->assertSame(
            $expected->getItemsToUpdate(),
            $changeSet->getItemsToUpdate(),
            'items to update'
        );

        $this->assertSame(
            $expected->getItemsToDelete(),
            $changeSet->getItemsToDelete(),
            'items to delete'
        );
    }

    public function provideFilesystemsAndExpectedChangeSets(): \Generator
    {
        $getFilesystemWithChanges = static function (callable ...$modifications): FilesystemAdapter {
            $adapter = new InMemoryFilesystemAdapter();
            $filesystem = new Filesystem($adapter);

            $filesystem->write('file1', 'fly');
            $filesystem->write('file2', 'me');
            $filesystem->createDirectory('foo');
            $filesystem->write('foo/file3', 'to the');
            $filesystem->createDirectory('foo/baz');
            $filesystem->write('foo/baz/file4', 'moon');
            $filesystem->createDirectory('bar');
            $filesystem->write('bar/file5a', '');
            $filesystem->write('bar/file5b', '');
            $filesystem->createDirectory('empty-dir');

            foreach ($modifications as $modification) {
                $modification($filesystem);
            }

            return $adapter;
        };

        $adapter1 = $getFilesystemWithChanges();
        $emptyChangeSet = new ChangeSet([], [], []);

        yield 'no changes; full sync' => [$adapter1, '', $emptyChangeSet];
        yield 'no changes; partial sync with directory' => [$adapter1, 'foo', $emptyChangeSet];
        yield 'no changes; partial sync with file' => [$adapter1, 'foo/file3', $emptyChangeSet];
        yield 'no changes; partial sync with multiple' => [$adapter1, ['foo', 'bar'], $emptyChangeSet];

        $adapter2 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->write('bar/new-file', 'new')
        );

        $changeSet2 = new ChangeSet(
            [
                ['hash' => '22af645d1859cb5ca6da0c484f1f37ea', 'path' => 'bar/new-file'],
            ],
            [
                'bar/' => ['hash' => 'c9baa6dc5b9218fb7bb83349ace1517b'],
            ],
            []
        );

        yield 'added file; full sync' => [$adapter2, '', $changeSet2];
        yield 'added file; partial sync with directory' => [$adapter2, 'bar', $changeSet2];
        yield 'added file; partial sync with file' => [$adapter2, 'bar/new-file', $changeSet2];
        yield 'added file outside scope' => [$adapter2, 'foo', $emptyChangeSet];

        $adapter3 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->delete('file1'),
            static fn (Filesystem $fs) => $fs->delete('foo/baz/file4'),
        );

        $changeSet3 = new ChangeSet(
            [],
            [
                'foo/' => ['hash' => '9579cd3e9ff37b98c0bc5c702e4e5beb'],
                'foo/baz/' => ['hash' => 'd41d8cd98f00b204e9800998ecf8427e'],
            ],
            [
                'file1',
                'foo/baz/file4',
            ]
        );

        yield 'removed files; full sync' => [$adapter3, '', $changeSet3];
        yield 'removed files; partial sync with all affected' => [$adapter3, ['file1', 'foo/baz/file4'], $changeSet3];

        yield 'removed files; partial sync with directory' => [
            $adapter3,
            'foo',
            new ChangeSet(
                [],
                [
                    'foo/' => ['hash' => '9579cd3e9ff37b98c0bc5c702e4e5beb'],
                    'foo/baz/' => ['hash' => 'd41d8cd98f00b204e9800998ecf8427e'],
                ],
                [
                    'foo/baz/file4',
                ]
            ),
        ];

        yield 'removed files; partial sync with single file' => [
            $adapter3,
            'file1',
            new ChangeSet(
                [],
                [],
                [
                    'file1',
                ]
            ),
        ];

        $adapter4 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->move('foo/file3', 'bar/file3'),
        );

        $changeSet4 = new ChangeSet(
            [],
            [
                'bar/' => ['hash' => '8a33fd03a58a6e8e82c8bb5c38bde45f'],
                'foo/' => ['hash' => '0a12dc23f78b213ee41428f3c1090724'],
                'foo/file3' => ['path' => 'bar/file3'],
            ],
            []
        );

        yield 'moved file; full sync' => [$adapter4, '', $changeSet4];
        yield 'moved file; partial sync with source and target' => [$adapter4, ['foo/file3', 'bar/file3'], $changeSet4];

        yield 'file moved outside scope' => [
            $adapter4,
            'foo',
            new ChangeSet(
                [],
                [
                    'foo/' => ['hash' => '0a12dc23f78b213ee41428f3c1090724'],
                ],
                [
                    'foo/file3',
                ]
            ),
        ];

        $adapter5 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->move('foo/file3', 'foo/baz/track-me'),
        );

        $changeSet5 = new ChangeSet(
            [],
            [
                'foo/' => ['hash' => '7d4ff96366c1f971c052a092fca4a72e'],
                'foo/baz/' => ['hash' => '241e718d4016fe98aca816485e513129'],
                'foo/file3' => ['path' => 'foo/baz/track-me'],
            ],
            []
        );

        yield 'moved and renamed file (full sync)' => [$adapter5, '', $changeSet5];
        yield 'moved and renamed file (partial sync)' => [$adapter5, 'foo', $changeSet5];

        $adapter6 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->write('file1', 'new-content'),
            static fn (Filesystem $fs) => $fs->write('foo/file3', 'new-content'),
        );

        yield 'changed contents (full sync)' => [
            $adapter6,
            '',
            new ChangeSet(
                [],
                [
                    'foo/' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                    'file1' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                []
            ),
        ];

        yield 'changed contents (partial sync)' => [
            $adapter6,
            'foo',
            new ChangeSet(
                [],
                [
                    'foo/' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                []
            ),
        ];

        $adapter7 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->write('large', str_pad('A', 100)),
            static fn (Filesystem $fs) => $fs->write('too-large', str_pad('A', 101)),
            static fn (Filesystem $fs) => $fs->write('bar/'.Dbafs::FILE_MARKER_EXCLUDED, ''),
            static fn (Filesystem $fs) => $fs->write('foo/'.Dbafs::FILE_MARKER_PUBLIC, '')
        );

        yield 'large and ignored files' => [
            $adapter7,
            '',
            new ChangeSet(
                [
                    ['hash' => '7866a94bb1745dee3a9601b4a5518b71', 'path' => 'large'],
                ],
                [],
                [
                    'bar/',
                    'bar/file5a',
                    'bar/file5b',
                ]
            ),
        ];

        $adapter8 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->createDirectory('bar/foo'),
            static fn (Filesystem $fs) => $fs->createDirectory('bar/foo/baz'),
            static fn (Filesystem $fs) => $fs->move('foo/file3', 'bar/foo/file3'),
            static fn (Filesystem $fs) => $fs->move('foo/baz/file4', 'bar/foo/baz/file4'),
            static fn (Filesystem $fs) => $fs->deleteDirectory('foo'),
        );

        yield 'moved folder' => [
            $adapter8,
            '',
            new ChangeSet(
                [],
                [
                    'bar/' => ['hash' => '1bc91408dac4048892e3603f6e7f80b4'],
                    'foo/' => ['path' => 'bar/foo/'],
                    'foo/baz/' => ['path' => 'bar/foo/baz/'],
                    'foo/baz/file4' => ['path' => 'bar/foo/baz/file4'],
                    'foo/file3' => ['path' => 'bar/foo/file3'],
                ],
                []
            ),
        ];

        $adapter9 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->move('bar/file5a', 'file5a'),
            static fn (Filesystem $fs) => $fs->move('bar/file5b', 'file5b'),
        );

        yield 'tracking by name for files of same hash' => [
            $adapter9,
            '',
            new ChangeSet(
                [],
                [
                    'bar/' => ['hash' => 'd41d8cd98f00b204e9800998ecf8427e'],
                    'bar/file5a' => ['path' => 'file5a'],
                    'bar/file5b' => ['path' => 'file5b'],
                ],
                []
            ),
        ];

        $adapter10 = $getFilesystemWithChanges(
            static fn (Filesystem $fs) => $fs->createDirectory('new'),
            static fn (Filesystem $fs) => $fs->write('new/thing', 'abc'),
            static fn (Filesystem $fs) => $fs->write('new/'.Dbafs::FILE_MARKER_PUBLIC, ''),
            static fn (Filesystem $fs) => $fs->createDirectory('ignored'),
            static fn (Filesystem $fs) => $fs->write('ignored/'.Dbafs::FILE_MARKER_EXCLUDED, ''),
            static fn (Filesystem $fs) => $fs->move('file1', 'new/file1'),
            static fn (Filesystem $fs) => $fs->move('file2', 'new/new-name'),
            static fn (Filesystem $fs) => $fs->delete('bar/file5a'),
            static fn (Filesystem $fs) => $fs->write('foo/file3', 'new-content'),
        );

        yield 'various operations (full sync)' => [
            $adapter10,
            '',
            new ChangeSet(
                [
                    ['hash' => 'db8dc8bdfe4ed260523b7dc8a7082145', 'path' => 'new/'],
                    ['hash' => '900150983cd24fb0d6963f7d28e17f72', 'path' => 'new/thing'],
                ],
                [
                    'bar/' => ['hash' => '10a3f34a1736690a9dad608c53740aa5'],
                    'file1' => ['path' => 'new/file1'],
                    'file2' => ['path' => 'new/new-name'],
                    'foo/' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                [
                    'bar/file5a',
                ]
            ),
        ];

        yield 'various operations (partial sync)' => [
            $adapter10,
            'foo',
            new ChangeSet(
                [],
                [
                    'foo/' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                []
            ),
        ];
    }

    public function testSync(): void
    {
        /*
         * Demo file structure present in the database:
         *
         *  <root>
         *    |-file1
         *    |-file2
         *    |-empty-dir
         *    |  |- ~
         *    |-foo
         *    |  |-file3
         *    |  |-baz
         *    |    |-file4
         *    |-bar
         *       |-file5a
         *       |-file5b
         *
         * File 5a and 5b have the same hash (e.g. both empty files).
         */

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllNumeric')
            ->with("SELECT path, uuid, hash, IF(type='folder', 1, 0) AS is_dir FROM tl_files", [], [])
            ->willReturn([
                ['files/foo', 'ee61', '48a6bbe07d25733e37e2c949ee412d5d', 1],
                ['files/bar.file', 'ab54', 'af17bc3b4a86a96a0f053a7e5f7c18ba', 0],
            ])
        ;

        $invocation = 0;

        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->with(
                $this->callback(
                    function (string $query): bool {
                        $this->assertStringStartsWith('INSERT INTO tl_files (`uuid`, `pid`, `path`, `hash`, `name`, `extension`, `type`, `tstamp`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $query);

                        return true;
                    }
                ),
                $this->callback(
                    function (array $parameters) use (&$invocation): bool {
                        $expectedParameters = [
                            [
                                // foo/file2.dat
                                1 => 'ee61', // pid
                                2 => 'files/foo/file2.dat', // path
                                3 => 'c13d88cb4cb02003daedb8a84e5d272a', // hash
                                4 => 'file2.dat', // name
                                5 => 'dat', // extension
                                6 => 'file', // type

                                // foo/file1.txt
                                9 => 'ee61', // pid
                                10 => 'files/foo/file1.txt', // path
                                11 => '22af645d1859cb5ca6da0c484f1f37ea', // hash
                                12 => 'file1.txt', // name
                                13 => 'txt', // extension
                                14 => 'file', // type
                            ],
                            [
                                // foo/sub/
                                1 => 'ee61', // pid
                                2 => 'files/foo/sub', // path
                                3 => 'd41d8cd98f00b204e9800998ecf8427e', // hash
                                4 => 'sub', // name
                                5 => '', // extension
                                6 => 'folder', // type
                            ],
                        ];

                        foreach ($expectedParameters[$invocation] as $index => $value) {
                            $this->assertSame($value, $parameters[$index], "INSERT query #$invocation, index $index");
                        }

                        ++$invocation;

                        return true;
                    }
                )
            )
        ;

        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_files',
                $this->callback(
                    function (array $changes): bool {
                        $this->assertSame('0f34431d95798f10bc55ee2e493a8818', $changes['hash']);
                        $this->assertArrayHasKey('tstamp', $changes);

                        return true;
                    }
                ),
                ['path' => 'files/foo']
            )
        ;

        $connection
            ->expects($this->once())
            ->method('delete')
            ->with(
                'tl_files',
                ['path' => 'files/bar.file']
            )
        ;

        $dbafs = $this->getDbafs($connection);
        $dbafs->setDatabasePathPrefix('files');

        // Lower bulk insert size so that we do not need excessive amounts of
        // operations when testing
        $dbafs->setBulkInsertSize(2);

        $adapter = new InMemoryFilesystemAdapter();
        $filesystem = new Filesystem($adapter);

        $filesystem->createDirectory('foo');
        $filesystem->createDirectory('foo/sub');
        $filesystem->write('foo/file1.txt', 'new');
        $filesystem->write('foo/file2.dat', 'stuff');
        $filesystem->delete('bar.file');

        $dbafs->sync($adapter);
    }

    private function getDbafs(Connection $connection): Dbafs
    {
        if ($connection instanceof MockObject) {
            $connection
                ->method('quoteIdentifier')
                ->with('tl_files')
                ->willReturn('tl_files')
            ;
        }

        return new Dbafs($connection, 'tl_files', 'md5');
    }
}
