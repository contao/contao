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

use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    public function testResult(): void
    {
        $result = new Result([new Hit(new Document('42', 'type', 'searchable'), 'test', 'https://foobar.com')]);
        $this->assertCount(1, $result->getHits());
    }
}
