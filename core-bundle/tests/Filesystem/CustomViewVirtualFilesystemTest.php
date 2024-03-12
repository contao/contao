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

use Contao\CoreBundle\Filesystem\CustomViewVirtualFilesystem;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Symfony\Component\Uid\Uuid;

class CustomViewVirtualFilesystemTest extends TestCase
{
    /**
     * @dataProvider provideViewConfigs
     */
    public function testValidatesViewConfig(array $config, string $expectedException): void
    {
        $virtualFilesystem = $this->createMock(VirtualFilesystemInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedException);

        new CustomViewVirtualFilesystem($virtualFilesystem, $config);
    }

    public function provideViewConfigs(): \Generator
    {
        yield 'invalid label' => [
            ['foo' => 'foo', 'bar/bar' => 'bar'],
            'A view label cannot contain slashes, got "bar/bar".',
        ];

        yield 'forbidden nesting of paths' => [
            ['1' => 'foo/bar', '2' => 'foo/bar2', '3' => 'foo/bar/baz'],
            'Invalid custom view configuration for virtual filesystem: Path "foo/bar/baz" is already covered by path "foo/bar".',
        ];

        yield 'empty label' => [
            ['' => 'foo'],
            'A view label cannot be empty.',
        ];
    }

    public function testTranslatesPaths(): void
    {
        $virtualFilesystem = $this->createMock(VirtualFilesystemInterface::class);
        $virtualFilesystem
            ->method('get')
            ->with('bar/baz/thing', VirtualFilesystemInterface::FORCE_SYNC)
            ->willReturn($item = new FilesystemItem(true, 'bar/baz/thing'))
        ;

        $customViewVirtualFilesystem = new CustomViewVirtualFilesystem(
            $virtualFilesystem,
            ['foo' => 'bar/baz']
        );

        $this->assertSame(
            $item,
            $customViewVirtualFilesystem->get('foo/thing', VirtualFilesystemInterface::FORCE_SYNC)
        );
    }

    public function testPassesThruUuids(): void
    {
        $virtualFilesystem = $this->createMock(VirtualFilesystemInterface::class);
        $virtualFilesystem
            ->method('get')
            ->with($uuid = Uuid::v1())
            ->willReturn($item = new FilesystemItem(true, 'bar/baz/thing'))
        ;

        $customViewVirtualFilesystem = new CustomViewVirtualFilesystem(
            $virtualFilesystem,
            ['foo' => 'bar/baz']
        );

        $this->assertSame(
            $item,
            $customViewVirtualFilesystem->get($uuid)
        );
    }

    public function testListsViews(): void
    {
        $mountManager = new MountManager();
        $mountManager->mount(new InMemoryFilesystemAdapter());

        $virtualFilesystem = new VirtualFilesystem($mountManager, $this->createMock(DbafsManager::class));

        $virtualFilesystem->createDirectory('foo');
        $virtualFilesystem->createDirectory('foo/dir');
        $virtualFilesystem->write('foo/dir/x1', '');
        $virtualFilesystem->write('foo/x2', '');
        $virtualFilesystem->write('foo/x3', '');
        $virtualFilesystem->createDirectory('bar');
        $virtualFilesystem->createDirectory('bar/baz');
        $virtualFilesystem->write('bar/baz/y1', '');
        $virtualFilesystem->write('z1', '');

        $customViewVirtualFilesystem = new CustomViewVirtualFilesystem(
            $virtualFilesystem,
            ['a' => 'foo', 'b' => 'bar/baz']
        );

        $items = $customViewVirtualFilesystem->listContents('', true)->toArray();

        $this->assertCount(5, $items);
        $this->assertSame('a/dir', $items[0]->getPath());
        $this->assertSame('a/dir/x1', $items[1]->getPath());
        $this->assertSame('a/x2', $items[2]->getPath());
        $this->assertSame('a/x3', $items[3]->getPath());
        $this->assertSame('b/y1', $items[4]->getPath());
    }

    public function testAccessIsRestrictedToViewBoundaries(): void
    {
        $virtualFilesystem = $this->createMock(VirtualFilesystemInterface::class);

        $customViewVirtualFilesystem = new CustomViewVirtualFilesystem(
            $virtualFilesystem,
            ['b' => 'foo/bar/bar']
        );

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Virtual filesystem path "../x" must not escape the view boundary.');

        $customViewVirtualFilesystem->get('b/../../x');
    }
}
