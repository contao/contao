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
     */
    public function testMergesTheValuesIntoTheGlobalsArray(array $globals, array $fragments, array $expected): void
    {
        $GLOBALS['TL_CTE'] = $globals;

        $listener = new GlobalsMapListener(['TL_CTE' => $fragments]);
        $listener->onInitializeSystem();

        $this->assertSame($expected, $GLOBALS['TL_CTE']);

        unset($GLOBALS['TL_CTE']);
    }

    public static function getValuesData(): iterable
    {
        yield 'add single' => [
            [],
            [['text' => 'HeadlineFragment']],
            ['text' => 'HeadlineFragment'],
        ];

        yield 'add group' => [
            [],
            [['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['headline' => 'HeadlineFragment']],
        ];

        yield 'add to existing group' => [
            ['texts' => ['text' => 'LegacyText']],
            [['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['text' => 'LegacyText', 'headline' => 'HeadlineFragment']],
        ];

        yield 'globals overrides fragment with priority 0' => [
            ['texts' => ['headline' => 'LegacyHeadline']],
            [['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['headline' => 'LegacyHeadline']],
        ];

        yield 'priority > 0 overrides globals' => [
            ['texts' => ['headline' => 'LegacyHeadline']],
            [1 => ['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['headline' => 'HeadlineFragment']],
        ];
    }
}
