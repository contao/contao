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
    public function testMergesTheValuesIntoTheGlobalsArray(array $existing, array $new, array $expected): void
    {
        $GLOBALS['TL_CTE'] = $existing;

        $listener = new GlobalsMapListener(['TL_CTE' => $new]);
        $listener->onInitializeSystem();

        $this->assertSame($expected, $GLOBALS['TL_CTE']);

        unset($GLOBALS['TL_CTE']);
    }

    public static function getValuesData(): iterable
    {
        yield 'add single' => [
            [],
            [0 => ['text' => 'HeadlineFragment']],
            ['text' => 'HeadlineFragment'],
        ];

        yield 'add group' => [
            [],
            [0 => ['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['headline' => 'HeadlineFragment']],
        ];

        yield 'add to existing group' => [
            ['texts' => ['text' => 'LegacyText']],
            [0 => ['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['text' => 'LegacyText', 'headline' => 'HeadlineFragment']],
        ];

        yield 'prefer default priority entries' => [
            ['texts' => ['headline' => 'LegacyHeadline']],
            [0 => ['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['headline' => 'HeadlineFragment']],
        ];

        yield 'keeps existing entries' => [
            ['texts' => ['headline' => 'LegacyHeadline']],
            [-1 => ['texts' => ['headline' => 'HeadlineFragment']]],
            ['texts' => ['headline' => 'LegacyHeadline']],
        ];
    }
}
