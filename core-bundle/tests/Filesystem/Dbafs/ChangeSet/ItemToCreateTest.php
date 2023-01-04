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

use Contao\CoreBundle\Filesystem\Dbafs\ChangeSet\ItemToCreate;
use Contao\CoreBundle\Tests\TestCase;

class ItemToCreateTest extends TestCase
{
    public function testCreateAndReadFromItem(): void
    {
        $directory = new ItemToCreate('e3ff', 'a', false);

        $this->assertSame('e3ff', $directory->getHash());
        $this->assertSame('a', $directory->getPath());
        $this->assertFalse($directory->isFile());
        $this->assertTrue($directory->isDirectory());

        $file = new ItemToCreate('a6b2', 'a/file', true);

        $this->assertSame('a6b2', $file->getHash());
        $this->assertSame('a/file', $file->getPath());
        $this->assertTrue($file->isFile());
        $this->assertFalse($file->isDirectory());
    }
}
