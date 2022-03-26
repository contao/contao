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

    public function getValuesData(): \Generator
    {
        yield 'add single' => [
            [],
            ['text' => 'HeadlineFragment'],
            ['text' => 'HeadlineFragment'],
        ];

        yield 'add group' => [
            [],
            ['texts' => ['headline' => 'HeadlineFragment']],
            ['texts' => ['headline' => 'HeadlineFragment']],
        ];

        yield 'add to existing group' => [
            ['texts' => ['text' => 'LegacyText']],
            ['texts' => ['headline' => 'HeadlineFragment']],
            ['texts' => ['headline' => 'HeadlineFragment', 'text' => 'LegacyText']],
        ];

        yield 'prefer existing entries' => [
            ['texts' => ['headline' => 'LegacyHeadline']],
            ['texts' => ['headline' => 'HeadlineFragment']],
            ['texts' => ['headline' => 'LegacyHeadline']],
        ];
    }
}
