<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\TestCase\FunctionalTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;

class DbafsTest extends FunctionalTestCase
{
    private VirtualFilesystem $filesystem;
    private Dbafs $dbafs;
    private FilesystemAdapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new VirtualFilesystem(
            (new MountManager())->mount($this->adapter = new InMemoryFilesystemAdapter()),
            $dbafsManager = new DbafsManager()
        );

        $container = $this->createClient()->getContainer();

        $this->dbafs = new Dbafs(
            new HashGenerator('md5', true),
            $container->get('database_connection'),
            $container->get('event_dispatcher'),
            $this->filesystem,
            'tl_files'
        );

        $dbafsManager->register($this->dbafs, '');
        $this->dbafs->useLastModified();

        static::resetDatabaseSchema();
    }

    public function testAlterFilesystemAndSync(): void
    {
        // Expect no changes initially
        $this->assertTrue($this->dbafs->sync()->isEmpty());

        // Write to the virtual filesystem; expect automatic syncing, thus no
        // changes when syncing
        $this->filesystem->write('file1', '1');
        $this->filesystem->createDirectory('123');
        $this->filesystem->write('123/file2', '2');

        $this->assertTrue($this->dbafs->sync()->isEmpty());

        // Directly write to the adapter; expect changes when syncing
        $this->adapter->move('file1', '123/file1', new Config());
        $this->adapter->write('file3', '3', new Config());

        $this->assertFile1MovedAndFile3Created($this->dbafs->sync());

        // Partial sync of 123 directory, then full sync
        $this->adapter->delete('123/file1');
        $this->adapter->delete('file3');

        $this->assertFile1Deleted($this->dbafs->sync('123/**'));
        $this->assertFile3Deleted($this->dbafs->sync());
    }

    public function testAutomaticallySyncsFiles(): void
    {
        // Adding a file should automatically update the Dbafs; we're using
        // paths with numeric strings here to also test their correct handling
        // inside the Dbafs class (see #5618).
        $this->filesystem->write('1', '');

        $contents = $this->filesystem->listContents('')->toArray();
        $this->assertCount(1, $contents);
        $this->assertSame('1', $contents[0]->getPath());

        // Moving a file should automatically update the Dbafs
        $this->filesystem->move('1', '2');

        $contents = $this->filesystem->listContents('')->toArray();
        $this->assertCount(1, $contents);
        $this->assertSame('2', $contents[0]->getPath());

        // Deleting a file should automatically update the Dbafs
        $this->filesystem->delete('2');

        $contents = $this->filesystem->listContents('')->toArray();
        $this->assertCount(0, $contents);
    }

    private function assertFile1MovedAndFile3Created(ChangeSet $changeSet): void
    {
        // Items to create
        $itemsToCreate = $changeSet->getItemsToCreate();
        $this->assertCount(1, $itemsToCreate);

        $this->assertSame('file3', $itemsToCreate[0]->getPath());
        $this->assertSame(md5('3'), $itemsToCreate[0]->getHash());
        $this->assertTrue($itemsToCreate[0]->isFile());

        // Items to update
        $itemsToUpdate = $changeSet->getItemsToUpdate();
        $this->assertCount(2, $itemsToUpdate);

        $this->assertSame('123', $itemsToUpdate[0]->getExistingPath());
        $this->assertFalse($itemsToUpdate[0]->updatesPath());
        $this->assertTrue($itemsToUpdate[0]->updatesHash());
        $this->assertSame('56840dad0dd1d66fe8f3c3a0f41879b1', $itemsToUpdate[0]->getNewHash());

        $this->assertSame('file1', $itemsToUpdate[1]->getExistingPath());
        $this->assertTrue($itemsToUpdate[1]->updatesPath());
        $this->assertFalse($itemsToUpdate[1]->updatesHash());
        $this->assertSame('123/file1', $itemsToUpdate[1]->getNewPath());

        // Items to delete
        $this->assertEmpty($changeSet->getItemsToDelete());
    }

    private function assertFile1Deleted(ChangeSet $changeSet): void
    {
        // Items to create
        $this->assertEmpty($changeSet->getItemsToCreate());

        // Items to update
        $itemsToUpdate = $changeSet->getItemsToUpdate();
        $this->assertCount(1, $itemsToUpdate);

        $this->assertSame('123', $itemsToUpdate[0]->getExistingPath());
        $this->assertFalse($itemsToUpdate[0]->updatesPath());
        $this->assertTrue($itemsToUpdate[0]->updatesHash());
        $this->assertSame('4fdc634444e398f6d8aed051313817e6', $itemsToUpdate[0]->getNewHash());

        // Items to delete
        $itemsToDelete = $changeSet->getItemsToDelete();
        $this->assertCount(1, $itemsToDelete);

        $this->assertSame('123/file1', $itemsToDelete[0]->getPath());
        $this->assertTrue($itemsToDelete[0]->isFile());
    }

    private function assertFile3Deleted(ChangeSet $changeSet): void
    {
        // Items to create
        $this->assertEmpty($changeSet->getItemsToCreate());

        // Items to update
        $this->assertEmpty($changeSet->getItemsToUpdate());

        // Items to delete
        $itemsToDelete = $changeSet->getItemsToDelete();
        $this->assertCount(1, $itemsToDelete);

        $this->assertSame('file3', $itemsToDelete[0]->getPath());
        $this->assertTrue($itemsToDelete[0]->isFile());
    }
}
