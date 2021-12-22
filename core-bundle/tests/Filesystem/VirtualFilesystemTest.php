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
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\Uid\Uuid;

class VirtualFilesystemTest extends TestCase
{
    private static Uuid $defaultUuid;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$defaultUuid = Uuid::v1();
    }

    // todo:
    //      fileExists

    public function testRead(): void
    {
        $mountManager = $this->mockMountManagerWithCall('read', [], 'foo');
        $filesystem = $this->getVirtualFilesystem($mountManager);

        $this->assertSame('foo', $filesystem->read('path'));
        $this->assertSame('foo', $filesystem->read(self::$defaultUuid));
    }

    public function testReadStream(): void
    {
        $resource = tmpfile();

        $mountManager = $this->mockMountManagerWithCall('readStream', [], $resource);
        $filesystem = $this->getVirtualFilesystem($mountManager);

        $this->assertSame($resource, $filesystem->readStream('path'));
        $this->assertSame($resource, $filesystem->readStream(self::$defaultUuid));

        fclose($resource);
    }

    public function testWrite(): void
    {
        $mountManager = $this->mockMountManagerWithCall('write', ['foo', ['some' => 'option']]);
        $filesystem = $this->getVirtualFilesystem($mountManager, ['prefix/path']);

        $filesystem->write('path', 'foo', ['some' => 'option']);
        $filesystem->write(self::$defaultUuid, 'foo', ['some' => 'option']);
    }

    public function testWriteStream(): void
    {
        $resource = tmpfile();

        $mountManager = $this->mockMountManagerWithCall('writeStream', [$resource, ['some' => 'option']]);
        $filesystem = $this->getVirtualFilesystem($mountManager, ['prefix/path']);

        $filesystem->writeStream('path', $resource, ['some' => 'option']);
        $filesystem->writeStream(self::$defaultUuid, $resource, ['some' => 'option']);

        fclose($resource);
    }

    public function testDelete(): void
    {
        $mountManager = $this->mockMountManagerWithCall('delete');
        $filesystem = $this->getVirtualFilesystem($mountManager, ['prefix/path']);

        $filesystem->delete('path');
        $filesystem->delete(self::$defaultUuid);
    }

    public function testDeleteDirectory(): void
    {
        $mountManager = $this->mockMountManagerWithCall('deleteDirectory');
        $filesystem = $this->getVirtualFilesystem($mountManager, ['prefix/path']);

        $filesystem->deleteDirectory('path');
        $filesystem->deleteDirectory(self::$defaultUuid);
    }

    public function testCreateDirectory(): void
    {
        $mountManager = $this->mockMountManagerWithCall('createDirectory', [['some' => 'option']]);
        $filesystem = $this->getVirtualFilesystem($mountManager, ['prefix/path']);

        $filesystem->createDirectory('path', ['some' => 'option']);
        $filesystem->createDirectory(self::$defaultUuid, ['some' => 'option']);
    }

    public function testCopy(): void
    {
        $mountManager = $this->mockMountManagerWithCall('copy', ['prefix/to/path', ['some' => 'option']]);
        $filesystem = $this->getVirtualFilesystem($mountManager, ['prefix/path', 'prefix/to/path']);

        $filesystem->copy('path', 'to/path', ['some' => 'option']);
        $filesystem->copy(self::$defaultUuid, 'to/path', ['some' => 'option']);
    }

    public function testMove(): void
    {
        $mountManager = $this->mockMountManagerWithCall('move', ['prefix/to/path', ['some' => 'option']]);
        $filesystem = $this->getVirtualFilesystem($mountManager, ['prefix/path', 'prefix/to/path']);

        $filesystem->move('path', 'to/path', ['some' => 'option']);
        $filesystem->move(self::$defaultUuid, 'to/path', ['some' => 'option']);
    }

    // todo:
    //      listContents

    /**
     * @dataProvider provideAccessFlags
     */
    public function testGetLastModified(int $accessFlags, bool $shouldSync, bool $shouldReadFromDbafs): void
    {
        $this->testGetMetadata(
            'lastModified',
            123450,
            $accessFlags,
            $shouldSync,
            $shouldReadFromDbafs
        );
    }

    /**
     * @dataProvider provideAccessFlags
     */
    public function testGetFileSize(int $accessFlags, bool $shouldSync, bool $shouldReadFromDbafs): void
    {
        $this->testGetMetadata(
            'fileSize',
            1024,
            $accessFlags,
            $shouldSync,
            $shouldReadFromDbafs
        );
    }

    /**
     * @dataProvider provideAccessFlags
     */
    public function testGetMimeType(int $accessFlags, bool $shouldSync, bool $shouldReadFromDbafs): void
    {
        $this->testGetMetadata(
            'mimeType',
            'image/png',
            $accessFlags,
            $shouldSync,
            $shouldReadFromDbafs
        );
    }

    public function provideAccessFlags(): \Generator
    {
        yield 'use DBAFS' => [
            VirtualFilesystemInterface::NONE, false, true,
        ];

        yield 'force sync' => [
            VirtualFilesystemInterface::FORCE_SYNC, true, true,
        ];

        yield 'bypass DBAFS' => [
            VirtualFilesystemInterface::BYPASS_DBAFS, false, false,
        ];

        yield 'bypass DBAFS, but still sync' => [
            VirtualFilesystemInterface::FORCE_SYNC | VirtualFilesystemInterface::BYPASS_DBAFS, true, false,
        ];
    }

    // todo:
    //       getExtraMetadata, setExtraMetadata

    /**
     * @dataProvider provideReadOnlyMethods
     *
     * @param mixed ...$arguments
     */
    public function testDisallowsMutatingAReadOnlyFilesystem(...$arguments): void
    {
        $method = array_shift($arguments);

        $readOnlyFilesystem = new VirtualFilesystem(
            $this->createMock(MountManager::class),
            $this->createMock(DbafsManager::class),
            '',
            true
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Tried to mutate a readonly filesystem instance.');

        $readOnlyFilesystem->$method(...$arguments);
    }

    public function provideReadOnlyMethods(): \Generator
    {
        yield 'write' => [
            'write', 'foo/bar', 'content',
        ];

        yield 'writeStream' => [
            'write', 'foo/bar', 'stream-content',
        ];

        yield 'delete' => [
            'delete', 'foo/bar',
        ];

        yield 'delete directory' => [
            'deleteDirectory', 'foo/bar',
        ];

        yield 'create directory' => [
            'createDirectory', 'foo/bar',
        ];

        yield 'copy' => [
            'copy', 'foo/bar', 'foo/baz',
        ];

        yield 'move' => [
            'move', 'foo/bar', 'foo/baz',
        ];

        yield 'set extra metadata' => [
            'setExtraMetadata', 'foo/bar', ['some' => 'data'],
        ];
    }

    /**
     * @param mixed $value
     */
    private function testGetMetadata(string $property, $value, int $accessFlags, bool $shouldSync, bool $shouldReadFromDbafs): void
    {
        $method = sprintf('get%s', ucfirst($property));

        $mountManager = $this->createMock(MountManager::class);
        $mountManager
            ->method($method)
            ->willReturn($value)
        ;

        $dbafsManager = $this->createMock(DbafsManager::class);
        $dbafsManager
            ->expects($this->once())
            ->method('resolveUuid')
            ->with(self::$defaultUuid, 'prefix')
            ->willReturn('prefix/path1')
        ;

        $dbafsManager
            ->expects($shouldSync ? $this->exactly(3) : $this->never())
            ->method('sync')
            ->with($this->callback(
                static fn (string $path) => \in_array($path, ['prefix/path1', 'prefix/path2'], true)
            ))
        ;

        $dbafsManager
            ->expects($shouldReadFromDbafs ? $this->exactly(3) : $this->never())
            ->method($method)
            ->willReturnMap([
                ['prefix/path1', $value],
                ['prefix/path2', null],
            ])
        ;

        $filesystem = new VirtualFilesystem($mountManager, $dbafsManager, 'prefix');

        $this->assertSame($value, $filesystem->$method(self::$defaultUuid, $accessFlags));
        $this->assertSame($value, $filesystem->$method('path1', $accessFlags));
        $this->assertSame($value, $filesystem->$method('path2', $accessFlags));
    }

    // todo:
    //      function testDefaultsToEmptyPrefix(): void

    /**
     * @param mixed $return
     */
    private function mockMountManagerWithCall(string $method, array $additionalArguments = [], $return = null): MountManager
    {
        $mountManager = $this->createMock(MountManager::class);

        $invocationMocker = $mountManager
            ->expects($this->exactly(2))
            ->method($method)
            ->with('prefix/path', ...$additionalArguments)
        ;

        if (null !== $return) {
            $invocationMocker->willReturn($return);
        }

        return $mountManager;
    }

    private function getVirtualFilesystem(MountManager $mountManager, array $sync = null): VirtualFilesystem
    {
        $dbafsManager = $this->createMock(DbafsManager::class);
        $dbafsManager
            ->expects($this->once())
            ->method('resolveUuid')
            ->with(self::$defaultUuid, 'prefix')
            ->willReturn('prefix/path')
        ;

        $dbafsManager
            ->expects(null !== $sync ? $this->exactly(2) : $this->never())
            ->method('sync')
            ->with(...($sync ?? []))
        ;

        return new VirtualFilesystem($mountManager, $dbafsManager, 'prefix');
    }
}
