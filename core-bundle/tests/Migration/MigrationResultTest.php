<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Migration;

use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Tests\TestCase;

class MigrationResultTest extends TestCase
{
    public function testGetters(): void
    {
        $result = new MigrationResult(true, '');

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('', $result->getMessage());

        $result = new MigrationResult(false, 'Message');

        $this->assertFalse($result->isSuccessful());
        $this->assertSame('Message', $result->getMessage());
    }
}
