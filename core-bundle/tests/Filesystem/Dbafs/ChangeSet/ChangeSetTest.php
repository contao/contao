<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem\Dbafs\ChangeSet;

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ChangeSet;
use Contao\CoreBundle\Tests\TestCase;

class ChangeSetTest extends TestCase
{
    public function testIsEmpty(): void
    {
        $changeSet1 = new ChangeSet([], [], [], ['foo' => 123450]);
        $changeSet2 = new ChangeSet([], [], [], []);

        $this->assertTrue($changeSet1->isEmpty());
        $this->assertFalse($changeSet1->isEmpty(true));

        $this->assertTrue($changeSet2->isEmpty());
        $this->assertTrue($changeSet2->isEmpty(true));
    }

    public function testGetItems(): void
    {
        $changeSet = new ChangeSet(
            [
                [ChangeSet::ATTR_HASH => 'a5e6', ChangeSet::ATTR_PATH => 'foo/new1', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                [ChangeSet::ATTR_HASH => 'd821', ChangeSet::ATTR_PATH => 'foo/new2', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_DIRECTORY],
            ],
            [
                'bar/old_path' => [ChangeSet::ATTR_PATH => 'bar/updated_path'],
                'bar/file_that_changes' => [ChangeSet::ATTR_HASH => 'e127'],
            ],
            [
                'baz' => ChangeSet::TYPE_DIRECTORY,
                'baz/deleted1' => ChangeSet::TYPE_FILE,
                'baz/deleted2' => ChangeSet::TYPE_FILE,
            ]
        );

        // Items to create
        $itemsToCreate = $changeSet->getItemsToCreate();
        $this->assertCount(2, $itemsToCreate);

        $this->assertSame('a5e6', $itemsToCreate[0]->getHash());
        $this->assertSame('foo/new1', $itemsToCreate[0]->getPath());
        $this->assertTrue($itemsToCreate[0]->isFile());

        $this->assertSame('d821', $itemsToCreate[1]->getHash());
        $this->assertSame('foo/new2', $itemsToCreate[1]->getPath());
        $this->assertTrue($itemsToCreate[1]->isDirectory());

        // Items to update
        $itemsToUpdate = $changeSet->getItemsToUpdate();
        $this->assertCount(2, $itemsToUpdate);

        $this->assertSame('bar/old_path', $itemsToUpdate[0]->getExistingPath());
        $this->assertTrue($itemsToUpdate[0]->updatesPath());
        $this->assertFalse($itemsToUpdate[0]->updatesHash());
        $this->assertSame('bar/updated_path', $itemsToUpdate[0]->getNewPath());

        $this->assertSame('bar/file_that_changes', $itemsToUpdate[1]->getExistingPath());
        $this->assertFalse($itemsToUpdate[1]->updatesPath());
        $this->assertTrue($itemsToUpdate[1]->updatesHash());
        $this->assertSame('e127', $itemsToUpdate[1]->getNewHash());

        // Items to delete
        $itemsToDelete = $changeSet->getItemsToDelete();
        $this->assertCount(3, $itemsToDelete);

        $this->assertSame('baz', $itemsToDelete[0]->getPath());
        $this->assertTrue($itemsToDelete[0]->isDirectory());

        $this->assertSame('baz/deleted1', $itemsToDelete[1]->getPath());
        $this->assertTrue($itemsToDelete[1]->isFile());

        $this->assertSame('baz/deleted2', $itemsToDelete[2]->getPath());
        $this->assertTrue($itemsToDelete[2]->isFile());
    }

    public function testGetUpdatesWithLastModified(): void
    {
        $changeSet = new ChangeSet(
            [],
            [
                'bar/old_path' => [ChangeSet::ATTR_PATH => 'bar/updated_path'],
                'bar/file_that_changes' => [ChangeSet::ATTR_HASH => 'e127'],
            ],
            [],
            [
                'bar/file_that_changes' => 123450,
                'foo/touched' => 234560,
            ]
        );

        $itemsToUpdate = $changeSet->getItemsToUpdate(true);
        $this->assertCount(3, $itemsToUpdate);

        $this->assertSame('foo/touched', $itemsToUpdate[0]->getExistingPath());
        $this->assertFalse($itemsToUpdate[0]->updatesPath());
        $this->assertFalse($itemsToUpdate[0]->updatesHash());
        $this->assertTrue($itemsToUpdate[0]->updatesLastModified());
        $this->assertSame(234560, $itemsToUpdate[0]->getLastModified());

        $this->assertSame('bar/old_path', $itemsToUpdate[1]->getExistingPath());
        $this->assertTrue($itemsToUpdate[1]->updatesPath());
        $this->assertFalse($itemsToUpdate[1]->updatesHash());
        $this->assertFalse($itemsToUpdate[1]->updatesLastModified());
        $this->assertSame('bar/updated_path', $itemsToUpdate[1]->getNewPath());

        $this->assertSame('bar/file_that_changes', $itemsToUpdate[2]->getExistingPath());
        $this->assertFalse($itemsToUpdate[2]->updatesPath());
        $this->assertTrue($itemsToUpdate[2]->updatesHash());
        $this->assertTrue($itemsToUpdate[2]->updatesLastModified());
        $this->assertSame('e127', $itemsToUpdate[2]->getNewHash());
        $this->assertSame(123450, $itemsToUpdate[2]->getLastModified());
    }

    public function testCreateEmpty(): void
    {
        $changeSet = ChangeSet::createEmpty();

        $this->assertSame([], $changeSet->getItemsToCreate());
        $this->assertSame([], $changeSet->getItemsToUpdate());
        $this->assertSame([], $changeSet->getItemsToDelete());
    }

    public function testWithOther(): void
    {
        $changeSet = new ChangeSet(
            [
                [ChangeSet::ATTR_HASH => 'a5e6', ChangeSet::ATTR_PATH => 'foo/new1', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                [ChangeSet::ATTR_HASH => '98c1', ChangeSet::ATTR_PATH => 'foo/new2', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
            ],
            [
                'foo' => [ChangeSet::ATTR_HASH => 'e127'],
                'foo/old_path' => [ChangeSet::ATTR_PATH => 'foo/updated_path'],
            ],
            [
                'foo/baz' => ChangeSet::TYPE_DIRECTORY,
                'foo/baz/deleted1' => ChangeSet::TYPE_FILE,
            ],
            [
                'foo/touched1' => 234560,
                'foo/touched2' => 345678,
            ]
        );

        $this->assertNotSame(
            $changeSet,
            $changeSet->withOther(ChangeSet::createEmpty()),
            'should create a new instance'
        );

        $newChangeSet = $changeSet->withOther(
            new ChangeSet(
                [
                    [ChangeSet::ATTR_HASH => '98c1', ChangeSet::ATTR_PATH => 'new2', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                    [ChangeSet::ATTR_HASH => 'bf6e', ChangeSet::ATTR_PATH => 'new3', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                ],
                [
                    '' => [ChangeSet::ATTR_HASH => '6628'],
                    'old_path' => [ChangeSet::ATTR_HASH => '8bba'],
                    'boring_file' => [ChangeSet::ATTR_PATH => 'interesting_file'],
                ],
                [
                    'baz' => ChangeSet::TYPE_DIRECTORY,
                    'baz/deleted1' => ChangeSet::TYPE_FILE,
                    'baz/deleted2' => ChangeSet::TYPE_FILE,
                ],
                [
                    'touched2' => 111111,
                    'touched3' => 456789,
                ]
            ),
            'foo'
        );

        // Items to create
        $itemsToCreate = $newChangeSet->getItemsToCreate();
        $this->assertCount(3, $itemsToCreate);

        $this->assertSame('a5e6', $itemsToCreate[0]->getHash());
        $this->assertSame('foo/new1', $itemsToCreate[0]->getPath());
        $this->assertTrue($itemsToCreate[0]->isFile());

        $this->assertSame('98c1', $itemsToCreate[1]->getHash());
        $this->assertSame('foo/new2', $itemsToCreate[1]->getPath());
        $this->assertTrue($itemsToCreate[1]->isFile());

        $this->assertSame('bf6e', $itemsToCreate[2]->getHash());
        $this->assertSame('foo/new3', $itemsToCreate[2]->getPath());
        $this->assertTrue($itemsToCreate[2]->isFile());

        // Items to update
        $itemsToUpdate = $newChangeSet->getItemsToUpdate();
        $this->assertCount(3, $itemsToUpdate);

        $this->assertSame('foo', $itemsToUpdate[0]->getExistingPath());
        $this->assertFalse($itemsToUpdate[0]->updatesPath());
        $this->assertTrue($itemsToUpdate[0]->updatesHash());
        $this->assertSame('6628', $itemsToUpdate[0]->getNewHash());

        $this->assertSame('foo/old_path', $itemsToUpdate[1]->getExistingPath());
        $this->assertTrue($itemsToUpdate[1]->updatesPath());
        $this->assertTrue($itemsToUpdate[1]->updatesHash());
        $this->assertSame('foo/updated_path', $itemsToUpdate[1]->getNewPath());
        $this->assertSame('8bba', $itemsToUpdate[1]->getNewHash());

        $this->assertSame('foo/boring_file', $itemsToUpdate[2]->getExistingPath());
        $this->assertTrue($itemsToUpdate[2]->updatesPath());
        $this->assertFalse($itemsToUpdate[2]->updatesHash());
        $this->assertSame('foo/interesting_file', $itemsToUpdate[2]->getNewPath());

        // Items to update with last modified
        $itemsToUpdateLastModified = $newChangeSet->getItemsToUpdate(true);
        $this->assertCount(6, $itemsToUpdateLastModified);

        $this->assertSame('foo/touched1', $itemsToUpdateLastModified[0]->getExistingPath());
        $this->assertFalse($itemsToUpdateLastModified[0]->updatesPath());
        $this->assertFalse($itemsToUpdateLastModified[0]->updatesHash());
        $this->assertTrue($itemsToUpdateLastModified[0]->updatesLastModified());
        $this->assertSame(234560, $itemsToUpdateLastModified[0]->getLastModified());

        $this->assertSame('foo/touched2', $itemsToUpdateLastModified[1]->getExistingPath());
        $this->assertFalse($itemsToUpdateLastModified[1]->updatesPath());
        $this->assertFalse($itemsToUpdateLastModified[1]->updatesHash());
        $this->assertTrue($itemsToUpdateLastModified[1]->updatesLastModified());
        $this->assertSame(111111, $itemsToUpdateLastModified[1]->getLastModified());

        $this->assertSame('foo/touched3', $itemsToUpdateLastModified[2]->getExistingPath());
        $this->assertFalse($itemsToUpdateLastModified[2]->updatesPath());
        $this->assertFalse($itemsToUpdateLastModified[2]->updatesHash());
        $this->assertTrue($itemsToUpdateLastModified[2]->updatesLastModified());
        $this->assertSame(456789, $itemsToUpdateLastModified[2]->getLastModified());

        $this->assertSame('foo', $itemsToUpdateLastModified[3]->getExistingPath());
        $this->assertFalse($itemsToUpdateLastModified[3]->updatesPath());
        $this->assertTrue($itemsToUpdateLastModified[3]->updatesHash());
        $this->assertFalse($itemsToUpdateLastModified[3]->updatesLastModified());
        $this->assertSame('6628', $itemsToUpdateLastModified[3]->getNewHash());

        $this->assertSame('foo/old_path', $itemsToUpdateLastModified[4]->getExistingPath());
        $this->assertTrue($itemsToUpdateLastModified[4]->updatesPath());
        $this->assertTrue($itemsToUpdateLastModified[4]->updatesHash());
        $this->assertFalse($itemsToUpdateLastModified[4]->updatesLastModified());
        $this->assertSame('foo/updated_path', $itemsToUpdateLastModified[4]->getNewPath());
        $this->assertSame('8bba', $itemsToUpdateLastModified[4]->getNewHash());

        $this->assertSame('foo/boring_file', $itemsToUpdateLastModified[5]->getExistingPath());
        $this->assertTrue($itemsToUpdateLastModified[5]->updatesPath());
        $this->assertFalse($itemsToUpdateLastModified[5]->updatesHash());
        $this->assertFalse($itemsToUpdateLastModified[5]->updatesLastModified());
        $this->assertSame('foo/interesting_file', $itemsToUpdateLastModified[5]->getNewPath());

        // Items to delete
        $itemsToDelete = $newChangeSet->getItemsToDelete();
        $this->assertCount(3, $itemsToDelete);

        $this->assertSame('foo/baz', $itemsToDelete[0]->getPath());
        $this->assertTrue($itemsToDelete[0]->isDirectory());

        $this->assertSame('foo/baz/deleted1', $itemsToDelete[1]->getPath());
        $this->assertTrue($itemsToDelete[1]->isFile());

        $this->assertSame('foo/baz/deleted2', $itemsToDelete[2]->getPath());
        $this->assertTrue($itemsToDelete[2]->isFile());
    }

    public function testHandlesNumericKeys(): void
    {
        $changeSet = new ChangeSet(
            [],
            [
                1 => [ChangeSet::ATTR_HASH => '6628'],
            ],
            [
                2 => ChangeSet::TYPE_DIRECTORY,
            ],
            [
                3 => 111111,
            ]
        );

        $newChangeSet = $changeSet->withOther(
            new ChangeSet(
                [],
                [
                    4 => [ChangeSet::ATTR_PATH => 'file'],
                ],
                [
                    5 => ChangeSet::TYPE_FILE,
                ],
                [
                    6 => 111111,
                ]
            ),
            'foo'
        );

        $this->assertCount(0, $newChangeSet->getItemsToCreate());
        $this->assertCount(4, $newChangeSet->getItemsToUpdate(true));
        $this->assertCount(2, $newChangeSet->getItemsToDelete());
    }
}
