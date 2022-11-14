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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet;
use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\Dbafs\Hashing\HashGenerator;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\TestCase\FunctionalTestCase;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Symfony\Component\HttpFoundation\Request;

class DbafsTest extends FunctionalTestCase
{
    public function testAlterFilesystemAndSync(): void
    {
        [$filesystem, $dbafs, $adapter] = $this->setupFilesystemAndDbafs();

        // Expect no changes initially
        $this->assertTrue($dbafs->sync()->isEmpty());

        // Write to the virtual filesystem; expect automatic syncing, thus no
        // changes when syncing
        $filesystem->write('file1', '1');
        $filesystem->createDirectory('foo');
        $filesystem->write('foo/file2', '2');

        $this->assertTrue($dbafs->sync()->isEmpty());

        // Directly write to the adapter; expect changes when syncing
        $adapter->move('file1', 'foo/file1', new Config());
        $adapter->write('file3', '3', new Config());

        $this->assertFile1MovedAndFile3Created($dbafs->sync());

        // Partial sync of foo directory, then full sync
        $adapter->delete('foo/file1');
        $adapter->delete('file3');

        $this->assertFile1Deleted($dbafs->sync('foo/**'));
        $this->assertFile3Deleted($dbafs->sync());
    }

    public function testAutomaticallySyncsFiles(): void
    {
        [$filesystem] = $this->setupFilesystemAndDbafs();

        // Adding a file should automatically update the Dbafs
        $filesystem->write('a', '');

        $contents = $filesystem->listContents('')->toArray();
        $this->assertCount(1, $contents);
        $this->assertSame('a', $contents[0]->getPath());

        // Moving a file should automatically update the Dbafs
        $filesystem->move('a', 'b');

        $contents = $filesystem->listContents('')->toArray();
        $this->assertCount(1, $contents);
        $this->assertSame('b', $contents[0]->getPath());

        // Deleting a file should automatically update the Dbafs
        $filesystem->delete('b');

        $contents = $filesystem->listContents('')->toArray();
        $this->assertCount(0, $contents);
    }

    private function assertFile1MovedAndFile3Created(ChangeSet $changeSet): void
    {
        $this->assertSame(
            [[ChangeSet::ATTR_HASH => md5('3'), ChangeSet::ATTR_PATH => 'file3', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE]],
            $changeSet->getItemsToCreate()
        );

        $this->assertSame(
            [
                'file1' => [ChangeSet::ATTR_PATH => 'foo/file1'],
                'foo' => [ChangeSet::ATTR_HASH => '56840dad0dd1d66fe8f3c3a0f41879b1'],
            ],
            $changeSet->getItemsToUpdate()
        );

        $this->assertEmpty($changeSet->getItemsToDelete());
    }

    private function assertFile1Deleted(ChangeSet $changeSet): void
    {
        $this->assertEmpty($changeSet->getItemsToCreate());

        $this->assertSame(
            [
                'foo' => [ChangeSet::ATTR_HASH => '4fdc634444e398f6d8aed051313817e6'],
            ],
            $changeSet->getItemsToUpdate()
        );

        $this->assertSame(
            [
                'foo/file1' => ChangeSet::TYPE_FILE,
            ],
            $changeSet->getItemsToDelete()
        );
    }

    private function assertFile3Deleted(ChangeSet $changeSet): void
    {
        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToUpdate());

        $this->assertSame(
            [
                'file3' => ChangeSet::TYPE_FILE,
            ],
            $changeSet->getItemsToDelete()
        );
    }

    /**
     * @return array{0: VirtualFilesystem, 1: Dbafs, 2:FilesystemAdapter}
     */
    private function setupFilesystemAndDbafs(): array
    {
        $client = $this->createClient();

        $container = $client->getContainer();
        $container->get('request_stack')->push(new Request());

        $filesystem = new VirtualFilesystem(
            (new MountManager())->mount($adapter = new InMemoryFilesystemAdapter()),
            $dbafsManager = new DbafsManager()
        );

        $dbafs = new Dbafs(
            new HashGenerator('md5', true),
            $container->get('database_connection'),
            $container->get('event_dispatcher'),
            $filesystem,
            'tl_files'
        );

        $dbafsManager->register($dbafs, '');
        $dbafs->useLastModified();

        static::resetDatabaseSchema();
        return [$filesystem, $dbafs, $adapter];
    }
}
