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
use Contao\CoreBundle\Filesystem\Dbafs\Dbafs;
use Contao\CoreBundle\Filesystem\Dbafs\DefaultFileHashProvider;
use Contao\CoreBundle\Filesystem\Storage;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;

class ChangeSetTest extends TestCase
{
    public function testEmptyChangeSetYieldsEmptyChangeLists(): void
    {
        $changeSet = new ChangeSet([], [], []);

        $this->assertTrue($changeSet->isEmpty());
        $this->assertEmpty($changeSet->getItemsToCreate());
        $this->assertEmpty($changeSet->getItemsToUpdate());
        $this->assertEmpty($changeSet->getItemsToDelete());
    }

    public function testChangeListReflectsInput(): void
    {
        $a = ['a'];
        $b = ['b'];
        $c = ['c'];
        $changeSet = new ChangeSet($a, $b, $c);

        $this->assertEquals($a, $changeSet->getItemsToCreate());
        $this->assertEquals($b, $changeSet->getItemsToUpdate());
        $this->assertEquals($c, $changeSet->getItemsToDelete());
    }
}
