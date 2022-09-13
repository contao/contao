<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Tests\TestCase;
use Contao\DC_Table;

class DataContainerTest extends TestCase
{
    /**
     * @dataProvider getCombinerValues
     */
    public function testCombiner(array $source, array $expected): void
    {
        $reflection = new \ReflectionClass(DC_Table::class);
        $names = $reflection->getMethod('combiner')->invoke($reflection->newInstanceWithoutConstructor(), $source);

        $this->assertSame($expected, array_values($names));
    }

    public function getCombinerValues(): array
    {
        return [
            [
                ['foo'],
                ['foo'],
            ],
            [
                ['foo', 'bar'],
                ['bar', 'foobar', 'foo'],
            ],
            [
                ['foo', 'bar', 'baz'],
                ['baz', 'barbaz', 'bar', 'foobar', 'foobarbaz', 'foobaz', 'foo'],
            ],
            [
                ['foo', 0, 'bar'],
                ['bar', '0bar', 'foo0', 'foo0bar', 'foobar', 'foo'],
            ],
            [
                ['foo', null, 'bar'],
                ['bar', 'foobar', 'foo'],
            ],
            [
                ['foo', false, 'bar'],
                ['bar', 'foobar', 'foo'],
            ],
            [
                ['foo', '', 'bar'],
                ['bar', 'foobar', 'foo'],
            ],
        ];
    }
}
