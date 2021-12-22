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

        $this->assertSame(
            [
                [ChangeSet::ATTR_HASH => 'a5e6', ChangeSet::ATTR_PATH => 'foo/new1', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                [ChangeSet::ATTR_HASH => 'd821', ChangeSet::ATTR_PATH => 'foo/new2', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_DIRECTORY],
            ],
            $changeSet->getItemsToCreate(),
            'items to create'
        );

        $this->assertSame(
            [
                'bar/old_path' => [ChangeSet::ATTR_PATH => 'bar/updated_path'],
                'bar/file_that_changes' => [ChangeSet::ATTR_HASH => 'e127'],
            ],
            $changeSet->getItemsToUpdate(),
            'items to update'
        );

        $this->assertSame(
            [
                'baz' => ChangeSet::TYPE_DIRECTORY,
                'baz/deleted1' => ChangeSet::TYPE_FILE,
                'baz/deleted2' => ChangeSet::TYPE_FILE,
            ],
            $changeSet->getItemsToDelete(),
            'items to delete'
        );
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

        $this->assertSame(
            [
                'foo/touched' => [ChangeSet::ATTR_LAST_MODIFIED => 234560],
                'bar/old_path' => [ChangeSet::ATTR_PATH => 'bar/updated_path'],
                'bar/file_that_changes' => [ChangeSet::ATTR_HASH => 'e127', ChangeSet::ATTR_LAST_MODIFIED => 123450],
            ],
            $changeSet->getItemsToUpdate(true),
        );
    }

    public function testCreateEmpty(): void
    {
        $changeSet = ChangeSet::createEmpty();

        $this->assertSame([], $changeSet->getItemsToCreate());
        $this->assertSame([], $changeSet->getItemsToUpdate());
        $this->assertSame([], $changeSet->getItemsToDelete());
        $this->assertSame([], $changeSet->getLastModifiedUpdates());
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

        $this->assertSame(
            [
                [ChangeSet::ATTR_HASH => 'a5e6', ChangeSet::ATTR_PATH => 'foo/new1', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                [ChangeSet::ATTR_HASH => '98c1', ChangeSet::ATTR_PATH => 'foo/new2', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                [ChangeSet::ATTR_HASH => 'bf6e', ChangeSet::ATTR_PATH => 'foo/new3', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
            ],
            $newChangeSet->getItemsToCreate(),
            'items to create'
        );

        $this->assertSame(
            [
                'foo' => [ChangeSet::ATTR_HASH => '6628'],
                'foo/old_path' => [ChangeSet::ATTR_PATH => 'foo/updated_path', ChangeSet::ATTR_HASH => '8bba'],
                'foo/boring_file' => [ChangeSet::ATTR_PATH => 'foo/interesting_file'],
            ],
            $newChangeSet->getItemsToUpdate(),
            'items to update'
        );

        $this->assertSame(
            [
                'foo/baz' => ChangeSet::TYPE_DIRECTORY,
                'foo/baz/deleted1' => ChangeSet::TYPE_FILE,
                'foo/baz/deleted2' => ChangeSet::TYPE_FILE,
            ],
            $newChangeSet->getItemsToDelete(),
            'items to delete'
        );

        $this->assertSame(
            [
                'foo/touched1' => 234560,
                'foo/touched2' => 111111,
                'foo/touched3' => 456789,
            ],
            $newChangeSet->getLastModifiedUpdates(),
            'last modified'
        );
    }
}
