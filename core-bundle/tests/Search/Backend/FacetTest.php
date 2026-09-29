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

use Contao\CoreBundle\Search\Backend\Facet;
use PHPUnit\Framework\TestCase;

class FacetTest extends TestCase
{
    public function testConvertsToArray(): void
    {
        $this->assertSame(
            ['key' => 'tl_page', 'label' => 'Pages', 'count' => 3],
            new Facet('tl_page', 'Pages', 3)->toArray(),
        );
    }
}
