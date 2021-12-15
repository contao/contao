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

use Contao\CoreBundle\Filesystem\ChangeSet;
use Contao\CoreBundle\Tests\TestCase;

class ChangeSetTest extends TestCase
{
    public function testIsEmpty(): void
    {
        $changeSet = new ChangeSet([], [], []);

        $this->assertTrue($changeSet->isEmpty());
    }

    public function testGetItems(): void
    {
        $changeSet = new ChangeSet(
            [
                [ChangeSet::ATTR_HASH => 'a5e6', ChangeSet::ATTR_PATH => 'foo/new1', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                [ChangeSet::ATTR_HASH => 'd821', ChangeSet::ATTR_PATH => 'foo/new2', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FOLDER],
            ],
            [
                'bar/old_path' => [ChangeSet::ATTR_PATH => 'bar/updated_path'],
                'bar/file_that_changes' => [ChangeSet::ATTR_HASH => 'e127'],
            ],
            [
                'baz' => ChangeSet::TYPE_FOLDER,
                'baz/deleted1' => ChangeSet::TYPE_FILE,
                'baz/deleted2' => ChangeSet::TYPE_FILE,
            ]
        );

        $this->assertSame(
            [
                [ChangeSet::ATTR_HASH => 'a5e6', ChangeSet::ATTR_PATH => 'foo/new1', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FILE],
                [ChangeSet::ATTR_HASH => 'd821', ChangeSet::ATTR_PATH => 'foo/new2', ChangeSet::ATTR_TYPE => ChangeSet::TYPE_FOLDER],
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
                'baz' => ChangeSet::TYPE_FOLDER,
                'baz/deleted1' => ChangeSet::TYPE_FILE,
                'baz/deleted2' => ChangeSet::TYPE_FILE,
            ],
            $changeSet->getItemsToDelete(),
            'items to delete'
        );
    }
}
