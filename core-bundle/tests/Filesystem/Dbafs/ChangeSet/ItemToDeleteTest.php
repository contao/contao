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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ItemToDelete;
use Contao\CoreBundle\Tests\TestCase;

class ItemToDeleteTest extends TestCase
{
    public function testCreateAndReadFromItem(): void
    {
        $directory = new ItemToDelete('a', false);

        $this->assertSame('a', $directory->getPath());
        $this->assertFalse($directory->isFile());
        $this->assertTrue($directory->isDirectory());

        $file = new ItemToDelete('a/file', true);

        $this->assertSame('a/file', $file->getPath());
        $this->assertTrue($file->isFile());
        $this->assertFalse($file->isDirectory());
    }
}
