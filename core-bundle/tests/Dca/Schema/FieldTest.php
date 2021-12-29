<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca\Schema;

use Contao\CoreBundle\Dca\Data;
use Contao\CoreBundle\Dca\Schema\Field;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    public function testIsExcluded(): void
    {
        $this->assertTrue((new Field('foo', new Data(['exclude' => true])))->isExcluded());
        $this->assertFalse((new Field('foo', new Data(['exclude' => false])))->isExcluded());
        $this->assertFalse((new Field('foo', new Data()))->isExcluded());
    }
}
