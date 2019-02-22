<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\GlobalsMapListener;
use Contao\CoreBundle\Tests\TestCase;

class GlobalsMapListenerTest extends TestCase
{
    /**
     * @dataProvider getValuesData
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMergesTheValuesIntoTheGlobalsArray(array $globals, array $values, array $expected): void
    {
        $GLOBALS = $globals;

        $listener = new GlobalsMapListener($values);
        $listener->onInitializeSystem();

        $this->assertSame($expected, $GLOBALS);
    }

    public function getValuesData(): \Generator
    {
        yield [
            [],
            ['foo' => 'bar'],
            ['foo' => 'bar'],
        ];

        yield [
            ['bar' => 'baz'],
            ['foo' => 'bar'],
            ['bar' => 'baz', 'foo' => 'bar'],
        ];

        yield [
            [],
            ['TL_CTE' => ['foo' => 'bar']],
            ['TL_CTE' => ['foo' => 'bar']],
        ];

        yield [
            ['TL_CTE' => ['bar' => 'baz']],
            ['TL_CTE' => ['foo' => 'bar']],
            ['TL_CTE' => ['bar' => 'baz', 'foo' => 'bar']],
        ];

        yield [
            ['TL_CTE' => ['foo' => 'bar']],
            ['TL_CTE' => ['foo' => 'baz']],
            ['TL_CTE' => ['foo' => 'baz']],
        ];

        yield [
            ['TL_CTE' => ['foo' => 'bar']],
            ['TL_CTE' => ['foo' => 'baz', 'bar' => 'baz']],
            ['TL_CTE' => ['foo' => 'baz', 'bar' => 'baz']],
        ];
    }
}
