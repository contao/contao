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
use Contao\CoreBundle\Filesystem\Dbafs\DbafsInterface;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\Context;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGeneratorInterface;
use Contao\CoreBundle\Filesystem\Dbafs\RetrieveDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\Dbafs\StoreDbafsMetadataEvent;
use Contao\CoreBundle\Filesystem\FilesystemItemIterator;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

class DbafsTest extends TestCase
{
    use ExpectDeprecationTrait;

    private int $codePageBackup = 0;

    protected function setUp(): void
    {
        if (\function_exists('sapi_windows_cp_get')) {
            $this->codePageBackup = sapi_windows_cp_get();
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (\function_exists('sapi_windows_cp_set')) {
            sapi_windows_cp_set($this->codePageBackup);
        }
    }

    public function testResolvePaths(): void
    {
        $uuid1 = $this->generateUuid(1);
        $uuid2 = $this->generateUuid(2);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturnMap([
                [
                    'SELECT * FROM tl_files WHERE id=?', [1], [],
                    ['id' => 1, 'uuid' => $uuid1->toBinary(), 'path' => 'foo/bar1', 'type' => 'file'],
                ],
                [
                    'SELECT * FROM tl_files WHERE uuid=?', [$uuid2->toBinary()], [],
                    ['id' => 2, 'uuid' => $uuid2->toBinary(), 'path' => 'foo/bar2', 'type' => 'file'],
                ],
            ])
        ;

        $dbafs = $this->getDbafs($connection);

        $this->assertSame('foo/bar1', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar2', $dbafs->getPathFromUuid($uuid2));

        // Subsequent calls (no matter which identifier) should be served from cache
        $this->assertSame('foo/bar1', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar1', $dbafs->getPathFromUuid($uuid1));
        $this->assertSame('foo/bar1', $dbafs->getRecord('foo/bar1')->getPath());
        $this->assertSame('foo/bar2', $dbafs->getPathFromId(2));
    }

    public function testResolvePathsForUnknownRecords(): void
    {
        $uuid = $this->generateUuid(1);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $dbafs = $this->getDbafs($connection);

        $this->assertNull($dbafs->getPathFromId(1));
        $this->assertNull($dbafs->getPathFromUuid($uuid));

        // Subsequent calls should be short-circuited
        $this->assertNull($dbafs->getPathFromId(1));
        $this->assertNull($dbafs->getPathFromUuid($uuid));
    }

    public function testGetRecord(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE path=?', ['foo/bar'], [])
            ->willReturn([
                'id' => 1,
                'uuid' => $this->generateUuid(1)->toBinary(),
                'path' => 'foo/bar',
                'type' => 'file',
            ])
        ;

        $dbafs = $this->getDbafs($connection);
        $record = $dbafs->getRecord('foo/bar');

        $this->assertNotNull($record);
        $this->assertSame('foo/bar', $record->getPath());
        $this->assertTrue($record->isFile());
        $this->assertSame('bar', $record->getExtraMetadata()['foo']); // defined via event
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
                ['id' => 1, 'uuid' => $this->generateUuid(1)->toBinary(), 'path' => 'foo/first', 'type' => 'file'],
                ['id' => 2, 'uuid' => $this->generateUuid(2)->toBinary(), 'path' => 'foo/second', 'type' => 'file'],
                ['id' => 3, 'uuid' => $this->generateUuid(3)->toBinary(), 'path' => 'foo/bar', 'type' => 'folder'],
            ])
        ;

        $dbafs = $this->getDbafs($connection);
        $records = iterator_to_array($dbafs->getRecords('foo'));

        $this->assertCount(3, $records);

        [$record1, $record2, $record3] = $records;

        $this->assertSame('foo/first', $record1->getPath());
        $this->assertTrue($record1->isFile());

        $this->assertSame('foo/second', $record2->getPath());
        $this->assertTrue($record2->isFile());

        $this->assertSame('foo/bar', $record3->getPath());
        $this->assertFalse($record3->isFile());
    }

    public function testGetMultipleRecordsNested(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAllAssociative')
            ->with('SELECT * FROM tl_files WHERE path LIKE ? ORDER BY path', ['foo/%'], [])
            ->willReturn([
                ['id' => 1, 'uuid' => $this->generateUuid(1)->toBinary(), 'path' => 'foo/first', 'type' => 'file'],
                ['id' => 2, 'uuid' => $this->generateUuid(2)->toBinary(), 'path' => 'foo/second', 'type' => 'file'],
                ['id' => 3, 'uuid' => $this->generateUuid(3)->toBinary(), 'path' => 'foo/bar', 'type' => 'folder'],
                ['id' => 4, 'uuid' => $this->generateUuid(4)->toBinary(), 'path' => 'foo/bar/third', 'type' => 'file'],
            ])
        ;

        $dbafs = $this->getDbafs($connection);
        $records = iterator_to_array($dbafs->getRecords('foo', true));

        $this->assertCount(4, $records);

        [$record1, $record2, $record3, $record4] = $records;

        $this->assertSame('foo/first', $record1->getPath());
        $this->assertTrue($record1->isFile());

        $this->assertSame('foo/second', $record2->getPath());
        $this->assertTrue($record2->isFile());

        $this->assertSame('foo/bar', $record3->getPath());
        $this->assertFalse($record3->isFile());

        $this->assertSame('foo/bar/third', $record4->getPath());
        $this->assertTrue($record4->isFile());
    }

    public function testSetExtraMetadata(): void
    {
        $uuid = $this->generateUuid(1);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn(['id' => 1, 'uuid' => $uuid->toBinary(), 'path' => 'some/path', 'type' => 'file'])
        ;

        $getColumn = function (string $name): Column {
            $column = $this->createMock(Column::class);
            $column
                ->method('getName')
                ->willReturn($name)
            ;

            return $column;
        };

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager
            ->method('listTableColumns')
            ->with('tl_files')
            ->willReturn(
                array_map(
                    $getColumn,
                    [
                        'id', 'pid', 'uuid', 'path',
                        'hash', 'lastModified', 'type',
                        'extension', 'found', 'name', 'tstamp',
                        'foo', 'baz',
                    ]
                )
            )
        ;

        $connection
            ->method('createSchemaManager')
            ->willReturn($schemaManager)
        ;

        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'tl_files',
                [
                    'foo' => 'normalized a',
                    'baz' => 'normalized c',
                ],
                ['uuid' => $uuid->toBinary()]
            )
        ;

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->method('dispatch')
            ->willReturnCallback(
                function ($event) use ($uuid) {
                    if (!$event instanceof StoreDbafsMetadataEvent) {
                        return $event;
                    }

                    $this->assertSame(
                        [
                            'uuid' => $uuid->toBinary(),
                            'path' => 'some/path',
                        ],
                        $event->getRow()
                    );

                    $this->assertSame(
                        [
                            'foo' => 'complex a',
                            'baz' => 'complex c',
                        ],
                        $event->getExtraMetadata()
                    );

                    $event->set('foo', 'normalized a');
                    $event->set('baz', 'normalized c');
                    $event->set('invalid', 'something');

                    return $event;
                }
            )
        ;

        $dbafs = $this->getDbafs($connection, null, $eventDispatcher);

        $dbafs->setExtraMetadata(
            'some/path',
            [
                'foo' => 'complex a',
                'bar' => 'complex b',
                'baz' => 'complex c',
            ]
        );
    }

    public function testSetExtraMetadataThrowsOnInvalidPath(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn(false)
        ;

        $dbafs = $this->getDbafs($connection);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Record for path "some/invalid/path" does not exist.');

        $dbafs->setExtraMetadata('some/invalid/path', []);
    }

    public function testSetExtraMetadataThrowsIfRecordIsADirectory(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->willReturn(
                ['id' => 1, 'uuid' => Uuid::v1()->toBinary(), 'path' => 'some/directory', 'type' => 'folder'],
            )
        ;

        $dbafs = $this->getDbafs($connection);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Can only set extra metadata for files, directory given under "some/directory".');

        $dbafs->setExtraMetadata('some/directory', []);
    }

    public function testNormalizesPathsIfDatabasePrefixWasSet(): void
    {
        $uuid = $this->generateUuid(1);

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE path=?', ['files/foo/bar'], [])
            ->willReturn([
                'id' => 1,
                'uuid' => $uuid->toBinary(),
                'path' => 'files/foo/bar',
                'type' => 'file',
            ])
        ;

        $dbafs = $this->getDbafs($connection);
        $dbafs->setDatabasePathPrefix('files');

        $record = $dbafs->getRecord('foo/bar');

        $this->assertNotNull($record);
        $this->assertSame('foo/bar', $record->getPath());

        $this->assertSame('foo/bar', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar', $dbafs->getPathFromUuid($uuid));
    }

    public function testResetInternalCache(): void
    {
        $uuid1 = $this->generateUuid(1);
        $uuid2 = $this->generateUuid(2);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE id=?', [1], [])
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'uuid' => $uuid1->toBinary(), 'path' => 'foo/bar', 'type' => 'file'],
                ['id' => 1, 'uuid' => $uuid2->toBinary(), 'path' => 'other/path', 'type' => 'file']
            )
        ;

        $dbafs = $this->getDbafs($connection);

        $this->assertSame('foo/bar', $dbafs->getPathFromId(1));
        $this->assertSame('foo/bar', $dbafs->getPathFromUuid($uuid1));
        $this->assertSame('foo/bar', $dbafs->getRecord('foo/bar')->getPath());

        $dbafs->reset();

        $this->assertSame('other/path', $dbafs->getPathFromId(1));
        $this->assertSame('other/path', $dbafs->getPathFromUuid($uuid2));
        $this->assertSame('other/path', $dbafs->getRecord('other/path')->getPath());
    }

    /**
     * @dataProvider provideSearchPaths
     *
     * @param array<int, string> $paths
     * @param array<int, string> $expectedSearchPaths
     * @param array<int, string> $expectedParentPaths
     */
    public function testNormalizesSearchPaths(array $paths, array $expectedSearchPaths, array $expectedParentPaths): void
    {
        $dbafs = $this->getDbafs();

        // Due to the complexity of the inner workings, we are testing a method
        // that isn't part of the API. Normalizing paths is the first isolated
        // step when synchronizing, but we do not want to expose this functionality.
        $method = new \ReflectionMethod($dbafs, 'getNormalizedSearchPaths');
        $method->setAccessible(true);

        [$searchPaths, $parentPaths] = $method->invoke($dbafs, ...$paths);

        $this->assertSame($expectedSearchPaths, $searchPaths, 'search paths');
        $this->assertSame($expectedParentPaths, $parentPaths, 'parent paths');
    }

    public function provideSearchPaths(): \Generator
    {
        yield 'single file' => [
            ['foo/bar/baz/cat.jpg'],
            ['foo/bar/baz/cat.jpg'],
            ['foo/bar/baz', 'foo/bar', 'foo'],
        ];

        yield 'parent covering children' => [
            ['foo/bar/baz/cat.jpg', 'foo/**'],
            ['foo'],
            [],
        ];

        yield 'individual files and (shared) parent folders' => [
            ['foo/from/cat.jpg', 'foo/to/cat.jpg'],
            ['foo/from/cat.jpg', 'foo/to/cat.jpg'],
            ['foo/to', 'foo/from', 'foo'],
        ];

        yield 'directories and (shared) parent folders' => [
            ['foo/bar/**', 'foo/bar/baz/**', 'other/**'],
            ['foo/bar', 'other'],
            ['foo'],
        ];

        yield 'parent with direct children not covering sub directories' => [
            ['foo/bar/baz/sub/**', 'foo/*'],
            ['foo//', 'foo/bar/baz/sub'],
            ['foo/bar/baz', 'foo/bar'],
        ];

        yield 'same directory with shallow and deep sync' => [
            ['foo/bar/*', 'foo/bar/**', 'foo/bar/*'],
            ['foo/bar'],
            ['foo'],
        ];

        yield 'resource as file and directory' => [
            ['foo/bar/*', 'foo/bar', 'other/thing', 'other/thing/**'],
            ['foo/bar//', 'foo/bar', 'other/thing'],
            ['other', 'foo'],
        ];

        yield 'various' => [
            ['foo/bar/baz', 'abc', 'other/thing/**', 'foo/bar/*', 'other/*', 'foo/bar/**'],
            ['abc', 'foo/bar', 'other//', 'other/thing'],
            ['foo'],
        ];
    }

    /**
     * @dataProvider provideInvalidSearchPaths
     *
     * @param array<int, string> $paths
     */
    public function testRejectsInvalidPaths(array $paths, string $expectedException): void
    {
        $dbafs = $this->getDbafs();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedException);

        $dbafs->computeChangeSet(...$paths);
    }

    public function provideInvalidSearchPaths(): \Generator
    {
        yield 'absolute path to file' => [
            ['foo', '/path/to/foo'],
            'Absolute path "/path/to/foo" is not allowed when synchronizing.',
        ];

        yield 'absolute path to directory' => [
            ['foo', '/path/to/foo/**'],
            'Absolute path "/path/to/foo/**" is not allowed when synchronizing.',
        ];

        yield 'unresolved relative path to file' => [
            ['../some/where'],
            'Dot path "../some/where" is not allowed when synchronizing.',
        ];

        yield 'unresolved relative path to directory' => [
            ['../some/where/**'],
            'Dot path "../some/where/**" is not allowed when synchronizing.',
        ];
    }

    /**
     * @dataProvider provideFilesystemsAndExpectedChangeSets
     *
     * @param string|array<int, string> $paths
     */
    public function testComputeChangeSet(VirtualFilesystemInterface $filesystem, $paths, ChangeSet $expected): void
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
            ->with("SELECT path, uuid, hash, IF(type='folder', 1, 0), NULL FROM tl_files", [], [])
            ->willReturn([
                ['file1', $this->generateUuid(1)->toBinary(), 'af17bc3b4a86a96a0f053a7e5f7c18ba', 0, null],
                ['file2', $this->generateUuid(2)->toBinary(), 'ab86a1e1ef70dff97959067b723c5c24', 0, null],
                ['empty-dir', $this->generateUuid(3)->toBinary(), 'd41d8cd98f00b204e9800998ecf8427e', 1, null],
                ['foo', $this->generateUuid(4)->toBinary(), '48a6bbe07d25733e37e2c949ee412d5d', 1, null],
                ['foo/file3', $this->generateUuid(5)->toBinary(), 'ead99c2fbd1b40a59695567afb14c26c', 0, null],
                ['foo/baz', $this->generateUuid(6)->toBinary(), '1ef7bcc6fe73d58905d2c8d21853663e', 1, null],
                ['foo/baz/file4', $this->generateUuid(7)->toBinary(), '6d4db5ff0c117864a02827bad3c361b9', 0, null],
                ['bar', $this->generateUuid(8)->toBinary(), '06a182c81a4f9c208a44b66fbb3c1d9f', 1, null],
                ['bar/file5a', $this->generateUuid(9)->toBinary(), 'd41d8cd98f00b204e9800998ecf8427e', 0, null],
                ['bar/file5b', $this->generateUuid(10)->toBinary(), 'd41d8cd98f00b204e9800998ecf8427e', 0, null],
            ])
        ;

        $dbafs = $this->getDbafs($connection, $filesystem);

        $changeSet = $dbafs->computeChangeSet(...((array) $paths));

        $this->assertSame($expected->getItemsToCreate(), $changeSet->getItemsToCreate(), 'items to create');
        $this->assertSame($expected->getItemsToUpdate(), $changeSet->getItemsToUpdate(), 'items to update');
        $this->assertSame($expected->getItemsToDelete(), $changeSet->getItemsToDelete(), 'items to delete');
    }

    public function provideFilesystemsAndExpectedChangeSets(): \Generator
    {
        $getFilesystem = function (): VirtualFilesystemInterface {
            $filesystem = new VirtualFilesystem(
                $this->getMountManagerWithRootAdapter(),
                $this->createMock(DbafsManager::class)
            );

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

            return $filesystem;
        };

        $filesystem1 = $getFilesystem();
        $emptyChangeSet = new ChangeSet([], [], []);

        yield 'no changes; full sync' => [$filesystem1, '', $emptyChangeSet];
        yield 'no changes; partial sync with directory' => [$filesystem1, 'foo/**', $emptyChangeSet];
        yield 'no changes; partial sync with file' => [$filesystem1, 'foo/file3', $emptyChangeSet];
        yield 'no changes; partial sync with multiple' => [$filesystem1, ['foo/*', 'bar/**'], $emptyChangeSet];

        $filesystem2 = $getFilesystem();
        $filesystem2->write('bar/new-file', 'new');

        $changeSet2 = new ChangeSet(
            [
                ['hash' => '22af645d1859cb5ca6da0c484f1f37ea', 'path' => 'bar/new-file', 'type' => ChangeSet::TYPE_FILE],
            ],
            [
                'bar' => ['hash' => 'c9baa6dc5b9218fb7bb83349ace1517b'],
            ],
            []
        );

        yield 'added file; full sync' => [$filesystem2, '', $changeSet2];
        yield 'added file; partial sync with directory' => [$filesystem2, 'bar/**', $changeSet2];
        yield 'added file; partial sync with file' => [$filesystem2, 'bar/new-file', $changeSet2];
        yield 'added file outside scope' => [$filesystem2, 'foo/**', $emptyChangeSet];

        $filesystem3 = $getFilesystem();
        $filesystem3->delete('file1');
        $filesystem3->delete('foo/baz/file4');

        $changeSet3 = new ChangeSet(
            [],
            [
                'foo' => ['hash' => '9579cd3e9ff37b98c0bc5c702e4e5beb'],
                'foo/baz' => ['hash' => 'd41d8cd98f00b204e9800998ecf8427e'],
            ],
            [
                'file1' => ChangeSet::TYPE_FILE,
                'foo/baz/file4' => ChangeSet::TYPE_FILE,
            ]
        );

        yield 'removed files; full sync' => [$filesystem3, '', $changeSet3];
        yield 'removed files; partial sync with all affected' => [$filesystem3, ['file1', 'foo/baz/file4'], $changeSet3];

        yield 'removed files; partial sync with directory' => [
            $filesystem3,
            'foo/**',
            new ChangeSet(
                [],
                [
                    'foo' => ['hash' => '9579cd3e9ff37b98c0bc5c702e4e5beb'],
                    'foo/baz' => ['hash' => 'd41d8cd98f00b204e9800998ecf8427e'],
                ],
                [
                    'foo/baz/file4' => ChangeSet::TYPE_FILE,
                ]
            ),
        ];

        yield 'removed files; partial sync with single file' => [
            $filesystem3,
            'file1',
            new ChangeSet(
                [],
                [],
                [
                    'file1' => ChangeSet::TYPE_FILE,
                ]
            ),
        ];

        $filesystem4 = $getFilesystem();
        $filesystem4->move('foo/file3', 'bar/file3');

        $changeSet4 = new ChangeSet(
            [],
            [
                'bar' => ['hash' => '8a33fd03a58a6e8e82c8bb5c38bde45f'],
                'foo' => ['hash' => '0a12dc23f78b213ee41428f3c1090724'],
                'foo/file3' => ['path' => 'bar/file3'],
            ],
            []
        );

        yield 'moved file; full sync' => [$filesystem4, '', $changeSet4];
        yield 'moved file; partial sync with source and target' => [$filesystem4, ['foo/file3', 'bar/file3'], $changeSet4];

        yield 'file moved outside scope' => [
            $filesystem4,
            'foo/**',
            new ChangeSet(
                [],
                [
                    'foo' => ['hash' => '0a12dc23f78b213ee41428f3c1090724'],
                ],
                [
                    'foo/file3' => ChangeSet::TYPE_FILE,
                ]
            ),
        ];

        $filesystem5 = $getFilesystem();
        $filesystem5->move('foo/file3', 'foo/baz/track-me');

        $changeSet5 = new ChangeSet(
            [],
            [
                'foo' => ['hash' => '7d4ff96366c1f971c052a092fca4a72e'],
                'foo/baz' => ['hash' => '241e718d4016fe98aca816485e513129'],
                'foo/file3' => ['path' => 'foo/baz/track-me'],
            ],
            []
        );

        yield 'moved and renamed file (full sync)' => [$filesystem5, '', $changeSet5];
        yield 'moved and renamed file (partial sync)' => [$filesystem5, 'foo/**', $changeSet5];

        $filesystem6 = $getFilesystem();
        $filesystem6->write('file1', 'new-content');
        $filesystem6->write('foo/file3', 'new-content');

        yield 'changed contents (full sync)' => [
            $filesystem6,
            '',
            new ChangeSet(
                [],
                [
                    'foo' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                    'file1' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                []
            ),
        ];

        yield 'changed contents (partial sync)' => [
            $filesystem6,
            'foo/**',
            new ChangeSet(
                [],
                [
                    'foo' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                []
            ),
        ];

        $filesystem7 = $getFilesystem();
        $filesystem7->write('bar/'.Dbafs::FILE_MARKER_EXCLUDED, '');
        $filesystem7->write('foo/'.Dbafs::FILE_MARKER_PUBLIC, '');

        yield 'ignored files' => [
            $filesystem7,
            '',
            new ChangeSet(
                [],
                [],
                [
                    'bar' => ChangeSet::TYPE_DIRECTORY,
                    'bar/file5a' => ChangeSet::TYPE_FILE,
                    'bar/file5b' => ChangeSet::TYPE_FILE,
                ]
            ),
        ];

        $filesystem8 = $getFilesystem();
        $filesystem8->createDirectory('bar/foo');
        $filesystem8->createDirectory('bar/foo/baz');
        $filesystem8->move('foo/file3', 'bar/foo/file3');
        $filesystem8->move('foo/baz/file4', 'bar/foo/baz/file4');
        $filesystem8->deleteDirectory('foo');

        yield 'moved folder' => [
            $filesystem8,
            '',
            new ChangeSet(
                [],
                [
                    'bar' => ['hash' => '1bc91408dac4048892e3603f6e7f80b4'],
                    'foo' => ['path' => 'bar/foo'],
                    'foo/baz' => ['path' => 'bar/foo/baz'],
                    'foo/baz/file4' => ['path' => 'bar/foo/baz/file4'],
                    'foo/file3' => ['path' => 'bar/foo/file3'],
                ],
                []
            ),
        ];

        $filesystem9 = $getFilesystem();
        $filesystem9->move('bar/file5a', 'file5a');
        $filesystem9->move('bar/file5b', 'file5b');

        yield 'tracking by name for files of same hash' => [
            $filesystem9,
            '',
            new ChangeSet(
                [],
                [
                    'bar' => ['hash' => 'd41d8cd98f00b204e9800998ecf8427e'],
                    'bar/file5a' => ['path' => 'file5a'],
                    'bar/file5b' => ['path' => 'file5b'],
                ],
                []
            ),
        ];

        $filesystem10 = $getFilesystem();
        $filesystem10->delete('foo/file3');
        $filesystem10->createDirectory('foo/file3');

        yield 'replacing a file with a folder of the same name' => [
            $filesystem10,
            '',
            new ChangeSet(
                [
                    ['hash' => 'd41d8cd98f00b204e9800998ecf8427e', 'path' => 'foo/file3', 'type' => ChangeSet::TYPE_DIRECTORY],
                ],
                [
                    'foo' => ['hash' => '5d93d7dddf717617c820c623e9b3168c'],
                ],
                [
                    'foo/file3' => ChangeSet::TYPE_FILE,
                ]
            ),
        ];

        $filesystem11 = $getFilesystem();
        $filesystem11->createDirectory('new');
        $filesystem11->write('new/thing', 'abc');
        $filesystem11->write('new/'.Dbafs::FILE_MARKER_PUBLIC, '');
        $filesystem11->createDirectory('ignored');
        $filesystem11->write('ignored/'.Dbafs::FILE_MARKER_EXCLUDED, '');
        $filesystem11->write('ignored/.DS_Store', '');
        $filesystem11->move('file1', 'new/file1');
        $filesystem11->move('file2', 'new/new-name');
        $filesystem11->delete('bar/file5a');
        $filesystem11->write('foo/file3', 'new-content');

        yield 'various operations (full sync)' => [
            $filesystem11,
            '',
            new ChangeSet(
                [
                    ['hash' => 'db8dc8bdfe4ed260523b7dc8a7082145', 'path' => 'new', 'type' => ChangeSet::TYPE_DIRECTORY],
                    ['hash' => '900150983cd24fb0d6963f7d28e17f72', 'path' => 'new/thing', 'type' => ChangeSet::TYPE_FILE],
                ],
                [
                    'bar' => ['hash' => '10a3f34a1736690a9dad608c53740aa5'],
                    'file1' => ['path' => 'new/file1'],
                    'file2' => ['path' => 'new/new-name'],
                    'foo' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                [
                    'bar/file5a' => ChangeSet::TYPE_FILE,
                ]
            ),
        ];

        yield 'various operations (partial sync)' => [
            $filesystem11,
            'foo/**',
            new ChangeSet(
                [],
                [
                    'foo' => ['hash' => '9158456b71197cf99a5b59fba00f77f1'],
                    'foo/file3' => ['hash' => 'e92c4f27d783ac09065352d0e0f7cb8b'],
                ],
                []
            ),
        ];
    }

    public function testSyncWithLastModified(): void
    {
        $filesystem = new VirtualFilesystem(
            $this->getMountManagerWithRootAdapter(),
            $this->createMock(DbafsManager::class)
        );

        $filesystem->write('old', 'foo'); // untouched
        $filesystem->write('file2', 'bar'); // moved
        $filesystem->write('new', 'baz'); // new

        $hashGenerator = $this->createMock(HashGeneratorInterface::class);
        $hashGenerator
            ->method('hashFileContent')
            ->willReturnCallback(
                function (VirtualFilesystemInterface $virtualFilesystem, string $path, Context $hashContext) use ($filesystem): void {
                    $this->assertSame($filesystem, $virtualFilesystem);

                    if ('old' === $path) {
                        $this->assertSame(99, $hashContext->getLastModified());
                        $this->assertTrue($hashContext->canSkipHashing());
                        $hashContext->skipHashing();

                        return;
                    }

                    if ('file2' === $path) {
                        $this->assertNull($hashContext->getLastModified());
                        $this->assertFalse($hashContext->canSkipHashing());
                        $hashContext->updateLastModified(200);
                        $hashContext->setHash('8446b');

                        return;
                    }

                    if ('new' === $path) {
                        $this->assertNull($hashContext->getLastModified());
                        $this->assertFalse($hashContext->canSkipHashing());
                        $hashContext->updateLastModified(201);
                        $hashContext->setHash('cbab7');
                    }
                }
            )
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->method('quoteIdentifier')
            ->with('tl_files')
            ->willReturn('tl_files')
        ;

        $connection
            ->expects($this->once())
            ->method('fetchAllNumeric')
            ->with("SELECT path, uuid, hash, IF(type='folder', 1, 0), lastModified FROM tl_files", [], [])
            ->willReturn([
                ['old', $this->generateUuid(1)->toBinary(), 'aa22b', 0, 99],
                ['file1', $this->generateUuid(2)->toBinary(), '8446b', 0, 100],
            ])
        ;

        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->with(
                'INSERT INTO tl_files (`uuid`, `pid`, `path`, `hash`, `type`, `name`, `extension`, `tstamp`, `lastModified`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $this->callback(
                    function (array $params) {
                        $this->assertSame('new', $params[2]); // path
                        $this->assertSame('cbab7', $params[3]); // hash
                        $this->assertSame(201, $params[8]); // lastModified

                        return true;
                    }
                )
            )
        ;

        $connection
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(
                function (string $table, array $update, array $criteria): void {
                    $this->assertSame('tl_files', $table);

                    $file = $criteria['path'] ?? null;

                    if ('file1' === $file) {
                        $this->assertSame('file2', $update['path']);

                        return;
                    }

                    if ('new' === $file) {
                        $this->assertSame(201, $update['lastModified']);

                        return;
                    }

                    $this->fail();
                }
            )
        ;

        $dbafs = new Dbafs(
            $hashGenerator,
            $connection,
            $this->createMock(EventDispatcherInterface::class),
            $filesystem,
            'tl_files'
        );

        $changeSet = $dbafs->sync();

        $this->assertSame(
            [
                [
                    ChangeSet::ATTR_HASH => 'cbab7',
                    ChangeSet::ATTR_PATH => 'new',
                    ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE,
                ],
            ],
            $changeSet->getItemsToCreate(),
        );

        $this->assertSame(
            [
                'new' => [ChangeSet::ATTR_LAST_MODIFIED => 201],
                'file1' => [ChangeSet::ATTR_PATH => 'file2', ChangeSet::ATTR_LAST_MODIFIED => 200],
            ],
            $changeSet->getItemsToUpdate(true),
        );

        $this->assertSame(
            [
                'new' => 201,
                'file1' => 200,
            ],
            $changeSet->getLastModifiedUpdates(),
        );

        $this->assertEmpty($changeSet->getItemsToDelete());
    }

    public function testSetsLastModifiedInRecords(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE path=?', ['foo'], [])
            ->willReturn([
                'id' => 1,
                'uuid' => $this->generateUuid(1)->toBinary(),
                'path' => 'foo',
                'type' => 'file',
                'lastModified' => 123450,
            ])
        ;

        $dbafs = $this->getDbafs($connection);
        $dbafs->useLastModified();

        $this->assertSame(123450, $dbafs->getRecord('foo')->getLastModified());
    }

    public function testSync(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllNumeric')
            ->with("SELECT path, uuid, hash, IF(type='folder', 1, 0), NULL FROM tl_files", [], [])
            ->willReturn([
                ['files/foo', 'ee61', '48a6bbe07d25733e37e2c949ee412d5d', 1, null],
                ['files/bar.file', 'ab54', 'af17bc3b4a86a96a0f053a7e5f7c18ba', 0, null],
                ['files/baz', 'cc12', '73feffa4b7f6bb68e44cf984c85f6e88', 1, null],
            ])
        ;

        $connection
            ->expects($this->once())
            ->method('fetchAssociative')
            ->with('SELECT * FROM tl_files WHERE path=?', ['files/baz'], [])
            ->willReturn([
                'id' => 1,
                'uuid' => $uuid = $this->generateUuid(1)->toBinary(),
                'path' => 'files/baz',
                'type' => 'file',
            ])
        ;

        $invokedInsert = 0;

        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->with(
                $this->callback(
                    function (string $query): bool {
                        $this->assertStringStartsWith('INSERT INTO tl_files (`uuid`, `pid`, `path`, `hash`, `type`, `name`, `extension`, `tstamp`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)', $query);

                        return true;
                    }
                ),
                $this->callback(
                    function (array $parameters) use (&$invokedInsert): bool {
                        $expectedParameters = [
                            [
                                // foo/file2.dat
                                1 => 'ee61', // pid
                                2 => 'files/foo/file2.dat', // path
                                3 => 'c13d88cb4cb02003daedb8a84e5d272a', // hash
                                4 => 'file', // type
                                5 => 'file2.dat', // name
                                6 => 'dat', // extension

                                // foo/file1.txt
                                9 => 'ee61', // pid
                                10 => 'files/foo/file1.txt', // path
                                11 => '22af645d1859cb5ca6da0c484f1f37ea', // hash
                                12 => 'file', // type
                                13 => 'file1.txt', // name
                                14 => 'txt', // extension
                            ],
                            [
                                // foo/sub/
                                1 => 'ee61', // pid
                                2 => 'files/foo/sub', // path
                                3 => 'd41d8cd98f00b204e9800998ecf8427e', // hash
                                4 => 'folder', // type
                                5 => 'sub', // name
                                6 => '', // extension
                            ],
                        ];

                        foreach ($expectedParameters[$invokedInsert] as $index => $value) {
                            $this->assertSame($value, $parameters[$index], "INSERT query #$invokedInsert, index $index");
                        }

                        ++$invokedInsert;

                        return true;
                    }
                )
            )
        ;

        $invokedUpdate = 0;

        $connection
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(
                function (string $table, array $updates, array $criteria) use (&$invokedUpdate): void {
                    $this->assertSame('tl_files', $table);
                    $this->assertArrayHasKey('tstamp', $updates);

                    if (0 === $invokedUpdate) {
                        $this->assertSame('files/baz2', $updates['path']);
                        $this->assertSame(['path' => 'files/baz'], $criteria);
                    } else {
                        $this->assertSame('0f34431d95798f10bc55ee2e493a8818', $updates['hash']);
                        $this->assertSame(['path' => 'files/foo'], $criteria);
                    }

                    ++$invokedUpdate;
                }
            )
        ;

        $connection
            ->expects($this->once())
            ->method('delete')
            ->with('tl_files', ['path' => 'files/bar.file', 'type' => 'file'])
        ;

        $filesystem = new VirtualFilesystem(
            $this->getMountManagerWithRootAdapter(),
            $this->createMock(DbafsManager::class)
        );

        $filesystem->createDirectory('foo');
        $filesystem->createDirectory('foo/sub');
        $filesystem->write('foo/file1.txt', 'new');
        $filesystem->write('foo/file2.dat', 'stuff');
        $filesystem->write('baz2', 'baz');
        $filesystem->delete('bar.file');

        $dbafs = $this->getDbafs($connection, $filesystem);
        $dbafs->setDatabasePathPrefix('files');

        // Lower bulk insert size so that we do not need excessive amounts of
        // operations when testing
        $dbafs->setBulkInsertSize(2);

        // Prime internal cache to test if it gets updated and still points to
        // this resource
        $this->assertSame($uuid, $dbafs->getRecord('baz')->getExtraMetadata()['uuid']->toBinary());

        $dbafs->sync();

        $this->assertSame($uuid, $dbafs->getRecord('baz2')->getExtraMetadata()['uuid']->toBinary());
    }

    public function testSyncWithMove(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllNumeric')
            ->with("SELECT path, uuid, hash, IF(type='folder', 1, 0), NULL FROM tl_files", [], [])
            ->willReturn([
                ['a', 'ee61', 'fdc43e4749862887eb87d5dde07c5cd8', 1, null],
                ['b', 'ab54', 'd41d8cd98f00b204e9800998ecf8427e', 1, null],
                ['a/file', 'cc12', 'acbd18db4cc2f85cedef654fccc4a4d8', 0, null],
            ])
        ;

        $expected = [
            [
                ['path' => 'a'],
                ['hash' => 'd41d8cd98f00b204e9800998ecf8427e'],
            ],
            [
                ['path' => 'a/file'],
                ['path' => 'b/file', 'pid' => 'ab54'], // updated path and uuid of "files/b"
            ],
            [
                ['path' => 'b'],
                ['hash' => 'fdc43e4749862887eb87d5dde07c5cd8'],
            ],
        ];

        $connection
            ->expects($this->exactly(3))
            ->method('update')
            ->willReturnCallback(
                function (string $table, array $updates, array $criteria) use (&$expected): void {
                    $this->assertSame('tl_files', $table);
                    $this->assertArrayHasKey('tstamp', $updates);

                    unset($updates['tstamp']);

                    [$expectedCriteria, $expectedUpdates] = array_shift($expected);

                    $this->assertSame($expectedCriteria, $criteria);
                    $this->assertSame($expectedUpdates, $updates);
                }
            )
        ;

        $filesystem = new VirtualFilesystem(
            $this->getMountManagerWithRootAdapter(),
            $this->createMock(DbafsManager::class)
        );

        $filesystem->createDirectory('a');
        $filesystem->createDirectory('b');
        $filesystem->write('b/file', 'foo');

        $dbafs = $this->getDbafs($connection, $filesystem);

        $dbafs->sync();
    }

    public function testSyncWithoutChanges(): void
    {
        $filesystem = $this->createMock(VirtualFilesystemInterface::class);
        $filesystem
            ->method('listContents')
            ->willReturn(new FilesystemItemIterator([]))
        ;

        $dbafs = $this->getDbafs(null, $filesystem);

        $this->assertTrue($dbafs->sync()->isEmpty());
    }

    public function testSupportsNoExtraFeaturesByDefault(): void
    {
        $dbafs = $this->getDbafs();
        $dbafs->useLastModified(false);

        $this->assertSame(DbafsInterface::FEATURES_NONE, $dbafs->getSupportedFeatures());
    }

    public function testSupportsLastModifiedWhenEnabled(): void
    {
        $dbafs = $this->getDbafs();
        $dbafs->useLastModified();

        $this->assertSame(DbafsInterface::FEATURE_LAST_MODIFIED, $dbafs->getSupportedFeatures());
    }

    /**
     * @group legacy
     */
    public function testSkipsNonUtf8FilesAndDirectories(): void
    {
        // Set a compatible codepage under Windows, so that dirname() calls
        // used in the InMemoryFilesystemAdapter implementation do not alter
        // our non-UTF-8 test paths.
        if (\function_exists('sapi_windows_cp_set')) {
            sapi_windows_cp_set(1252);
        }

        $filesystem = new VirtualFilesystem(
            $this->getMountManagerWithRootAdapter(),
            $this->createMock(DbafsManager::class)
        );

        $filesystem->createDirectory("b\xE4r");
        $filesystem->write("b\xE4r/file.txt", '');
        $filesystem->write("foob\xE4r.txt", '');
        $filesystem->write('valid.txt', '');

        $dbafs = $this->getDbafs(null, $filesystem);

        $this->expectDeprecation('Since contao/core-bundle 4.13: Filesystem resources with non-UTF-8 paths will no longer be skipped but throw an exception in Contao 5.0.');

        $changeSet = $dbafs->computeChangeSet();

        $this->assertCount(1, $changeSet->getItemsToCreate());
        $this->assertSame('valid.txt', $changeSet->getItemsToCreate()[0][ChangeSet::ATTR_PATH]);
    }

    private function getMountManagerWithRootAdapter(): MountManager
    {
        return (new MountManager())->mount(new InMemoryFilesystemAdapter());
    }

    private function getDbafs(Connection $connection = null, VirtualFilesystemInterface $filesystem = null, EventDispatcherInterface $eventDispatcher = null): Dbafs
    {
        $connection ??= $this->createMock(Connection::class);

        if ($connection instanceof MockObject) {
            $connection
                ->method('quoteIdentifier')
                ->with('tl_files')
                ->willReturn('tl_files')
            ;
        }

        if (null === $eventDispatcher) {
            $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
            $eventDispatcher
                ->method('dispatch')
                ->willReturnCallback(
                    static function (RetrieveDbafsMetadataEvent $event) {
                        $event->set('foo', 'bar');
                        $event->set('uuid', $event->getUuid());

                        return $event;
                    }
                )
            ;
        }

        $filesystem ??= $this->createMock(VirtualFilesystemInterface::class);

        $dbafs = new Dbafs(new HashGenerator('md5'), $connection, $eventDispatcher, $filesystem, 'tl_files');
        $dbafs->useLastModified(false);

        return $dbafs;
    }

    /**
     * Generate reproducible UUIDs.
     */
    private function generateUuid(int $index): Uuid
    {
        $hash = md5((string) $index);

        $uuid = sprintf(
            '%08s-%04s-1%03s-8000-000000000000',
            substr($hash, -8),
            substr($hash, -12, 4),
            substr($hash, -15, 3),
        );

        return Uuid::fromString($uuid);
    }
}
