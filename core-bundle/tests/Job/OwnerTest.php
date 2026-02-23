<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Job;

use Contao\CoreBundle\Job\Owner;
use PHPUnit\Framework\TestCase;

class OwnerTest extends TestCase
{
    public function testCanRetrieveIdentifier(): void
    {
        $owner = new Owner(42);
        $this->assertSame(42, $owner->getId());
        $this->assertFalse($owner->isSystem());
    }

    public function testAsSystemReturnsOwnerWithSystemIdentifier(): void
    {
        $owner = Owner::asSystem();
        $this->assertSame(Owner::SYSTEM, $owner->getId());
        $this->assertTrue($owner->isSystem());
    }
}
