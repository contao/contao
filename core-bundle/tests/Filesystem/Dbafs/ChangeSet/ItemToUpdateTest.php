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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ItemToUpdate;
use Contao\CoreBundle\Tests\TestCase;

class ItemToUpdateTest extends TestCase
{
    public function testCreateAndReadFromItem(): void
    {
        // Everything changes
        $item1 = new ItemToUpdate('a', 'e3ff', 'b', null);

        $this->assertSame('a', $item1->getExistingPath());
        $this->assertTrue($item1->updatesPath());
        $this->assertTrue($item1->updatesHash());
        $this->assertTrue($item1->updatesLastModified());
        $this->assertSame('b', $item1->getNewPath());
        $this->assertSame('e3ff', $item1->getNewHash());
        $this->assertNull($item1->getLastModified());

        // Only path changes
        $item2 = new ItemToUpdate('a', null, 'b', false);

        $this->assertTrue($item2->updatesPath());
        $this->assertFalse($item2->updatesHash());
        $this->assertFalse($item2->updatesLastModified());
        $this->assertSame('b', $item2->getNewPath());

        // Only hash changes
        $item3 = new ItemToUpdate('a', 'fa25', null, false);

        $this->assertFalse($item3->updatesPath());
        $this->assertTrue($item3->updatesHash());
        $this->assertFalse($item3->updatesLastModified());
        $this->assertSame('fa25', $item3->getNewHash());

        // Only last modified changes
        $item3 = new ItemToUpdate('a', null, null, 6789);

        $this->assertFalse($item3->updatesPath());
        $this->assertFalse($item3->updatesHash());
        $this->assertTrue($item3->updatesLastModified());
        $this->assertSame(6789, $item3->getLastModified());
    }

    public function testThrowsWhenAccessingPathThatWasNotSet(): void
    {
        $item = new ItemToUpdate('a', '12de', null, null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The update to item "a" does not include a new path.');

        $item->getNewPath();
    }

    public function testThrowsWhenAccessingHashThatWasNotSet(): void
    {
        $item = new ItemToUpdate('a', null, 'b', null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The update to item "a" does not include a new hash.');

        $item->getNewHash();
    }

    public function testThrowsWhenAccessingLastModifiedThatWasNotSet(): void
    {
        $item = new ItemToUpdate('a', '12de', null, false);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The update to item "a" does not include a last modified date.');

        $item->getLastModified();
    }
}
