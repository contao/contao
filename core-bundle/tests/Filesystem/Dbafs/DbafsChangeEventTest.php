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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsChangeEvent;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Tests\TestCase;

class DbafsChangeEventTest extends TestCase
{
    public function testGetChangeSet(): void
    {
        $event = new DbafsChangeEvent($changeSet = new ChangeSet([], [], []));

        $this->assertSame($changeSet, $event->getChangeSet());
    }

    public function testGetFilesystemItems(): void
    {
        $event = new DbafsChangeEvent(
            new ChangeSet(
                [
                    ['hash' => '123', 'path' => 'files/foo', 'type' => ChangeSet::TYPE_FILE],
                    ['hash' => '123', 'path' => 'files/dir1', 'type' => ChangeSet::TYPE_DIRECTORY],
                ],
                [
                    'files/bar' => ['path' => 'files/bar2'],
                    'files/dir2' => ['hash' => '123'],
                ],
                [
                    'files/baz' => ChangeSet::TYPE_FILE,
                    'files/dir3' => ChangeSet::TYPE_DIRECTORY,
                ],
            ),
        );

        $storage = $this->createMock(VirtualFilesystem::class);
        $storage
            ->method('getPrefix')
            ->willReturn('files')
        ;

        $storage
            ->method('get')
            ->willReturnMap([
                ['foo', VirtualFilesystemInterface::NONE, new FilesystemItem(true, 'foo')],
                ['bar2', VirtualFilesystemInterface::NONE, new FilesystemItem(true, 'bar2')],
                ['dir1', VirtualFilesystemInterface::NONE, new FilesystemItem(false, 'dir1')],
                ['dir2', VirtualFilesystemInterface::NONE, new FilesystemItem(false, 'dir2')],
            ])
        ;

        $createdElements = $event->getCreatedFilesystemItems($storage)->toArray();
        $this->assertCount(2, $createdElements);

        $this->assertTrue($createdElements[0]->isFile());
        $this->assertSame('foo', $createdElements[0]->getPath());
        $this->assertFalse($createdElements[1]->isFile());
        $this->assertSame('dir1', $createdElements[1]->getPath());

        $updatedElements = $event->getUpdatedFilesystemItems($storage)->toArray();
        $this->assertCount(3, $updatedElements);

        $this->assertTrue($updatedElements[0]->isFile());
        $this->assertSame('bar2', $updatedElements[0]->getPath());
        $this->assertTrue($updatedElements[1]->isFile());
        $this->assertSame('bar', $updatedElements[1]->getPath());
        $this->assertFalse($updatedElements[2]->isFile());
        $this->assertSame('dir2', $updatedElements[2]->getPath());

        $deletedElements = $event->getDeletedFilesystemItems($storage)->toArray();
        $this->assertCount(2, $deletedElements);

        $this->assertTrue($deletedElements[0]->isFile());
        $this->assertSame('baz', $deletedElements[0]->getPath());
        $this->assertFalse($deletedElements[1]->isFile());
        $this->assertSame('dir3', $deletedElements[1]->getPath());
    }
}
