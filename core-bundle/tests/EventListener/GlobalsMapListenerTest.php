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
     * @param array|string|null $existing
     * @param array|string      $new
     *
     * @dataProvider getValuesData
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMergesTheValuesIntoTheGlobalsArray(string $key, $existing, $new): void
    {
        $GLOBALS[$key] = $existing;

        $listener = new GlobalsMapListener([$key => $new]);
        $listener->onInitializeSystem();

        $this->assertSame($new, $GLOBALS[$key]);
    }

    public function getValuesData(): \Generator
    {
        yield ['foo', null, 'bar'];
        yield ['foo', 'bar', 'baz'];

        yield ['TL_CTE', null, ['foo' => 'bar']];
        yield ['TL_CTE', ['foo' => 'bar'], ['foo' => 'baz']];
    }
}
