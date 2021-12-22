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
use Contao\CoreBundle\Filesystem\Dbafs\DbafsInterface;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\Dbafs\UnableToResolveUuidException;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Uuid;

class DbafsManagerTest extends TestCase
{
    public function testRegisterAndMatchDbafs(): void
    {
        $manager = new DbafsManager();

        $manager->register(
            $this->getDbafsWithProperties(true, false, false),
            'foo'
        );

        $manager->register(
            $this->getDbafsWithProperties(true, true, false),
            'foo/bar'
        );

        $manager->register(
            $this->getDbafsWithProperties(false, false, false),
            'baz'
        );

        $this->assertTrue($manager->match('foo'));
        $this->assertTrue($manager->match('foo/foo.file'));
        $this->assertTrue($manager->match('foo/bar'));
        $this->assertTrue($manager->match('foo/bar/some/bar.file'));
        $this->assertTrue($manager->match('baz/*'));

        $this->assertFalse($manager->match('foobar'));
        $this->assertFalse($manager->match('baz/../foobar'));
    }

    /**
     * @dataProvider provideInvalidConfigurations
     */
    public function testValidatesTransitiveProperties(array $paths, string $exception): void
    {
        $manager = new DbafsManager();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage($exception);

        foreach ($paths as $path => $dbafsItem) {
            $manager->register($dbafsItem, $path);
        }
    }

    public function provideInvalidConfigurations(): \Generator
    {
        yield 'more specific one which does not support last modified should be reported' => [
            [
                'files' => $this->getDbafsWithProperties(true, false, false),
                'files/media' => $this->getDbafsWithProperties(false, false, false),
            ],
            "The transitive property 'supportsLastModified' must be true for any DBAFS with a path prefix 'files/media', because it its also true for 'files'.",
        ];

        yield 'should ignore valid configurations in between' => [
            [
                'abc' => $this->getDbafsWithProperties(false, true, false),
                'files' => $this->getDbafsWithProperties(false, true, true),
                'abc/def' => $this->getDbafsWithProperties(true, true, true),
                'files/media' => $this->getDbafsWithProperties(false, false, true),
            ],
            "The transitive property 'supportsFileSize' must be true for any DBAFS with a path prefix 'files/media', because it its also true for 'files'.",
        ];

        yield 'make sure nested folders work as well' => [
            [
                'foo' => $this->getDbafsWithProperties(false, false, true),
                'foo/bar/baz' => $this->getDbafsWithProperties(true, true, false),
            ],
            "The transitive property 'supportsMimeType' must be true for any DBAFS with a path prefix 'foo/bar/baz', because it its also true for 'foo'.",
        ];

        yield 'adding a less specific one that covers more than the children should be reported' => [
            [
                'foo/bar' => $this->getDbafsWithProperties(false, true, true),
                '' => $this->getDbafsWithProperties(true, true, true),
            ],
            "The transitive property 'supportsLastModified' must be true for any DBAFS with a path prefix 'foo/bar', because it its also true for ''.",
        ];
    }

    public function testResolveUuid(): void
    {
        $uuid1 = Uuid::v1();
        $uuid2 = Uuid::v1();
        $uuid3 = Uuid::v1();

        $manager = new DbafsManager();

        $manager->register(
            $this->getDbafsCoveringUuids([
                'foo/a' => $uuid1,
                'foo/bar/b' => $uuid2,
            ]),
            'foo'
        );

        $manager->register(
            $this->getDbafsCoveringUuids([
                'other/c' => $uuid3,
            ]),
            'other'
        );

        // Resolve without constraining to prefix
        $this->assertSame('foo/a', $manager->resolveUuid($uuid1));
        $this->assertSame('foo/bar/b', $manager->resolveUuid($uuid2));
        $this->assertSame('other/c', $manager->resolveUuid($uuid3));

        // Resolve with constraining to prefix
        $this->assertSame('foo/a', $manager->resolveUuid($uuid1, 'foo'));
        $this->assertSame('foo/bar/b', $manager->resolveUuid($uuid2, 'foo'));
        $this->assertSame('other/c', $manager->resolveUuid($uuid3, 'other'));

        // Nothing should be found outside the constrained scope
        $this->expectException(UnableToResolveUuidException::class);
        $manager->resolveUuid($uuid3, 'foo');
    }

    public function testResourceExists(): void
    {
        $dbafs = $this->createMock(DbafsInterface::class);
        $dbafs
            ->method('getRecord')
            ->willReturnCallback(
                function (string $path): ?FilesystemItem {
                    if (\in_array($path, ['bar/baz', 'bar.file'], true)) {
                        return $this->createMock(FilesystemItem::class);
                    }

                    return null;
                }
            )
        ;

        $manager = new DbafsManager();
        $manager->register($dbafs, 'foo');

        $this->assertTrue($manager->resourceExists('foo/bar.file'));
        $this->assertTrue($manager->resourceExists('foo/bar/baz'));
        $this->assertFalse($manager->resourceExists('foo/other'));
        $this->assertFalse($manager->resourceExists('foobar'));
    }

    public function testGetLastModified(): void
    {
        $dbafs1 = $this->getDbafsWithProperties(true, false, false);
        $dbafs1
            ->method('getRecord')
            ->with('bar')
            ->willReturn(new FilesystemItem(true, 'bar', 123450))
        ;

        $dbafs2 = $this->getDbafsWithProperties(false, false, false);
        $dbafs2
            ->expects($this->never())
            ->method('getRecord')
        ;

        $manager = new DbafsManager();
        $manager->register($dbafs1, 'foo');
        $manager->register($dbafs2, '');

        $this->assertSame(123450, $manager->getLastModified('foo/bar'));
        $this->assertNull($manager->getLastModified('other'));
    }

    public function testGetFileSize(): void
    {
        $dbafs1 = $this->getDbafsWithProperties(false, true, false);
        $dbafs1
            ->method('getRecord')
            ->with('bar')
            ->willReturn(new FilesystemItem(true, 'bar', 0, 1024))
        ;

        $dbafs2 = $this->getDbafsWithProperties(false, false, false);
        $dbafs2
            ->expects($this->never())
            ->method('getRecord')
        ;

        $manager = new DbafsManager();
        $manager->register($dbafs1, 'foo');
        $manager->register($dbafs2, '');

        $this->assertSame(1024, $manager->getFileSize('foo/bar'));
        $this->assertNull($manager->getFileSize('other'));
    }

    public function testGetMimeType(): void
    {
        $dbafs1 = $this->getDbafsWithProperties(false, false, true);
        $dbafs1
            ->method('getRecord')
            ->with('bar')
            ->willReturn(new FilesystemItem(true, 'bar', 0, 0, 'image/png'))
        ;

        $dbafs2 = $this->getDbafsWithProperties(false, false, false);
        $dbafs2
            ->expects($this->never())
            ->method('getRecord')
        ;

        $manager = new DbafsManager();
        $manager->register($dbafs1, 'foo');
        $manager->register($dbafs2, '');

        $this->assertSame('image/png', $manager->getMimeType('foo/bar'));
        $this->assertNull($manager->getMimeType('other'));
    }

    public function testGetExtraMetadata(): void
    {
        $filesDbafs = $this->getDbafsWithExtraMetadata(
            'media/hilarious-cat.mov',
            [
                'foo' => 'foobar',
                'bar' => 42,
            ]
        );

        $filesMediaDbafs = $this->getDbafsWithExtraMetadata(
            'hilarious-cat.mov',
            [
                'baz' => true,
            ]
        );

        $manager = new DbafsManager();
        $manager->register($filesDbafs, 'files');
        $manager->register($filesMediaDbafs, 'files/media');

        $this->assertSame(
            [
                'foo' => 'foobar',
                'bar' => 42,
                'baz' => true,
            ],
            $manager->getExtraMetadata('files/media/hilarious-cat.mov')
        );
    }

    public function testValidatesExtraMetadata(): void
    {
        $assetsDbafs = $this->getDbafsWithExtraMetadata(
            'images/a.jpg',
            [
                'accessed' => 123,
                'compressed' => true,
                'quality' => 'high',
            ]
        );

        $assetsImagesDbafs = $this->getDbafsWithExtraMetadata(
            'a.jpg',
            [
                'aspectRatio' => 1.5,
                'quality' => '50',
                'compressed' => true,
            ]
        );

        $manager = new DbafsManager();
        $manager->register($assetsDbafs, 'assets');
        $manager->register($assetsImagesDbafs, 'assets/images');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The metadata key(s) "quality", "compressed" appeared in more than one matching DBAFS for path "assets/images/a.jpg".');

        $manager->getExtraMetadata('assets/images/a.jpg');
    }

    public function testSetExtraMetadata(): void
    {
        $dbafs1 = $this->createMock(DbafsInterface::class);
        $dbafs1
            ->expects($this->once())
            ->method('setExtraMetadata')
            ->with('bar/baz', ['some' => 'value'])
        ;

        $dbafs2 = $this->createMock(DbafsInterface::class);
        $dbafs2
            ->expects($this->once())
            ->method('setExtraMetadata')
            ->with('baz', ['some' => 'value'])
            ->willThrowException(new \InvalidArgumentException()) // should be ignored
        ;

        $dbafs3 = $this->createMock(DbafsInterface::class);
        $dbafs3
            ->expects($this->never())
            ->method('setExtraMetadata')
        ;

        $manager = new DbafsManager();
        $manager->register($dbafs1, 'foo');
        $manager->register($dbafs2, 'foo/bar');
        $manager->register($dbafs3, 'other');

        $manager->setExtraMetadata('foo/bar/baz', ['some' => 'value']);
    }

    public function testSetExtraMetadataFailsIfNoResourceExists(): void
    {
        $dbafs1 = $this->createMock(DbafsInterface::class);
        $dbafs1
            ->expects($this->once())
            ->method('setExtraMetadata')
            ->with('bar/baz', ['some' => 'value'])
            ->willThrowException(new \InvalidArgumentException())
        ;

        $dbafs2 = $this->createMock(DbafsInterface::class);
        $dbafs2
            ->expects($this->once())
            ->method('setExtraMetadata')
            ->with('baz', ['some' => 'value'])
            ->willThrowException(new \InvalidArgumentException())
        ;

        $manager = new DbafsManager();
        $manager->register($dbafs1, 'foo');
        $manager->register($dbafs2, 'foo/bar');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("No resource exists for the given path 'foo/bar/baz'.");

        $manager->setExtraMetadata('foo/bar/baz', ['some' => 'value']);
    }

    /**
     * @dataProvider provideListModes
     */
    public function testListContents(bool $deep): void
    {
        $dbafs1 = $this->getDbafsListingRecords(
            'bar',
            ['bar', 'baz', 'bar/file1'],
            $deep,
        );

        $dbafs2 = $this->getDbafsListingRecords(
            '',
            ['file1', 'file2'],
            $deep,
        );

        $manager = new DbafsManager();
        $manager->register($dbafs1, 'foo');
        $manager->register($dbafs2, 'foo/bar');

        $listing = $manager->listContents('foo/bar', $deep);

        $this->assertSame(
            [
                'foo/bar/file1',
                'foo/bar/file2',
                'foo/bar',
                'foo/baz',
            ],
            array_map('strval', iterator_to_array($listing))
        );
    }

    public function provideListModes(): \Generator
    {
        yield 'shallow' => [false];
        yield 'deep' => [true];
    }

    public function testSync(): void
    {
        $filesDbafs = $this->createMock(DbafsInterface::class);
        $filesDbafs
            ->expects($this->once())
            ->method('sync')
            ->with('foo/*', 'foo/bar/**')
            ->willReturn(
                new ChangeSet(
                    [],
                    [],
                    ['foo/bar/file1' => ChangeSet::TYPE_FILE],
                )
            )
        ;

        $filesFooBarDbafs = $this->createMock(DbafsInterface::class);
        $filesFooBarDbafs
            ->expects($this->once())
            ->method('sync')
            ->with('**')
            ->willReturn(
                new ChangeSet(
                    [],
                    [],
                    ['file2' => ChangeSet::TYPE_FILE],
                )
            )
        ;

        $assetsDbafs = $this->createMock(DbafsInterface::class);
        $assetsDbafs
            ->expects($this->never())
            ->method('sync')
        ;

        $manager = new DbafsManager();
        $manager->register($filesDbafs, 'files');
        $manager->register($filesFooBarDbafs, 'files/foo/bar');
        $manager->register($assetsDbafs, 'assets');

        $changeSet = $manager->sync('files/foo/*', 'files/foo/bar/**', 'baz');

        $this->assertSame(
            [
                'files/foo/bar/file1' => ChangeSet::TYPE_FILE,
                'files/foo/bar/file2' => ChangeSet::TYPE_FILE,
            ],
            $changeSet->getItemsToDelete()
        );
    }

    private function getDbafsListingRecords(string $path, array $listing, bool $deep): DbafsInterface
    {
        $dbafs = $this->createMock(DbafsInterface::class);

        $dbafs
            ->method('getRecords')
            ->with($path, $deep)
            ->willReturn(
                array_map(
                    static fn (string $listingPath): FilesystemItem => new FilesystemItem(true, $listingPath),
                    $listing
                )
            )
        ;

        return $dbafs;
    }

    /**
     * @return DbafsInterface&MockObject
     */
    private function getDbafsWithProperties(bool $supportsLastModified, bool $supportsFileSize, bool $supportsMimeType): DbafsInterface
    {
        $dbafs = $this->createMock(DbafsInterface::class);

        $dbafs
            ->method('supportsLastModified')
            ->willReturn($supportsLastModified)
        ;

        $dbafs
            ->method('supportsFileSize')
            ->willReturn($supportsFileSize)
        ;

        $dbafs
            ->method('supportsMimeType')
            ->willReturn($supportsMimeType)
        ;

        return $dbafs;
    }

    /**
     * @param array<string, Uuid> $mapping
     */
    private function getDbafsCoveringUuids(array $mapping): DbafsInterface
    {
        $dbafs = $this->createMock(DbafsInterface::class);
        $dbafs
            ->method('getPathFromUuid')
            ->willReturnCallback(
                static function (Uuid $uuidToCompare) use ($mapping): ?string {
                    foreach ($mapping as $path => $uuid) {
                        if (0 === $uuidToCompare->compare($uuid)) {
                            return $path;
                        }
                    }

                    return null;
                }
            )
        ;

        return $dbafs;
    }

    private function getDbafsWithExtraMetadata(string $path, array $extraMetadata): DbafsInterface
    {
        $dbafs = $this->createMock(DbafsInterface::class);
        $dbafs
            ->method('getRecord')
            ->with($path)
            ->willReturn(
                new FilesystemItem(
                    true,
                    $path,
                    0,
                    0,
                    'application/x-empty',
                    $extraMetadata
                )
            )
        ;

        return $dbafs;
    }
}
