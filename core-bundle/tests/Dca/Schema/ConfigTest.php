<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Dca\Schema;

use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\Schema\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testIsEditable(): void
    {
        $this->assertFalse((new Config('config', new Data(['notEditable' => true])))->isEditable());
        $this->assertTrue((new Config('config', new Data(['notEditable' => false])))->isEditable());
        $this->assertTrue((new Config('config', new Data()))->isEditable());
    }

    public function testIsClosed(): void
    {
        $this->assertTrue((new Config('config', new Data(['closed' => true])))->isClosed());
        $this->assertFalse((new Config('config', new Data(['closed' => false])))->isClosed());
        $this->assertFalse((new Config('config', new Data()))->isClosed());
    }
}
