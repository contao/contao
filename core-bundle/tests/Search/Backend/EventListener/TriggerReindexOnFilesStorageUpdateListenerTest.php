<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\EventListener;

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsChangeEvent;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Search\Backend\BackendSearch;
use Contao\CoreBundle\Search\Backend\EventListener\TriggerReindexOnFilesStorageUpdateListener;
use Contao\CoreBundle\Search\Backend\ReindexConfig;
use Contao\CoreBundle\Tests\TestCase;

class TriggerReindexOnFilesStorageUpdateListenerTest extends TestCase
{
    public function testInvokeTriggersReindexOnFilesStorageElements(): void
    {
        $storage = $this->createStub(VirtualFilesystem::class);
        $storage
            ->method('getPrefix')
            ->willReturn('files')
        ;

        $storage
            ->method('get')
            ->willReturnMap([
                ['foo', VirtualFilesystemInterface::NONE, new FilesystemItem(true, 'foo')],
                ['bar2', VirtualFilesystemInterface::NONE, new FilesystemItem(true, 'bar2')],
                ['meta', VirtualFilesystemInterface::NONE, new FilesystemItem(true, 'meta')],
                ['dir1', VirtualFilesystemInterface::NONE, new FilesystemItem(false, 'dir1')],
                ['dir2', VirtualFilesystemInterface::NONE, new FilesystemItem(false, 'dir2')],
            ])
        ;

        $backendSearch = $this->createMock(BackendSearch::class);
        $backendSearch
            ->expects($this->once())
            ->method('reindex')
            ->with($this->callback(
                static fn (ReindexConfig $config) => ['contao.vfs.files' => [
                    'foo', // created file
                    'bar2', // renamed file (new)
                    'bar', // renamed file (old)
                    'meta', // file with updated metadata
                    'baz', // deleted file
                ]] === $config->getLimitedDocumentIds()->toArray(),
            ))
        ;

        $event = new DbafsChangeEvent(
            new ChangeSet(
                [
                    ['hash' => '123', 'path' => 'files/foo', 'type' => ChangeSet::TYPE_FILE],
                    ['hash' => '123', 'path' => 'files/dir1', 'type' => ChangeSet::TYPE_DIRECTORY],
                ],
                [
                    'files/bar' => ['path' => 'files/bar2'],
                    'files/dir2' => ['hash' => '123'],
                    'files/meta' => [],
                ],
                [
                    'files/baz' => ChangeSet::TYPE_FILE,
                    'files/dir3' => ChangeSet::TYPE_DIRECTORY,
                ],
            ),
        );

        $listener = new TriggerReindexOnFilesStorageUpdateListener($backendSearch, $storage);
        $listener($event);
    }
}
