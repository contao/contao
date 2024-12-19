<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend;

use Contao\CoreBundle\Search\Backend\Query;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function testQuery(): void
    {
        $query = new Query(20, 'keywords', 'type', 'tag');

        $this->assertSame(20, $query->getPerPage());
        $this->assertSame('keywords', $query->getKeywords());
        $this->assertSame('type', $query->getType());
        $this->assertSame('tag', $query->getTag());
    }

    public function testEquals(): void
    {
        $this->assertTrue((new Query(20, 'keywords', 'type', 'tag'))->equals(new Query(20, 'keywords', 'type', 'tag')));
        $this->assertFalse((new Query(20, 'something', 'type', 'tag'))->equals(new Query(20, 'keywords', 'type', 'tag')));
    }
}
