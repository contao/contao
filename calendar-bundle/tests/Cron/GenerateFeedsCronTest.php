<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\Cron;

use Contao\Calendar;
use Contao\CalendarBundle\Cron\GenerateFeedsCron;
use Contao\TestCase\ContaoTestCase;

class GenerateFeedsCronTest extends ContaoTestCase
{
    public function testExecutesGenerateFeeds(): void
    {
        $calendarUtil = $this->createMock(Calendar::class);
        $calendarUtil
            ->expects($this->once())
            ->method('generateFeeds')
        ;

        $framework = $this->mockContaoFramework([], [Calendar::class => $calendarUtil]);

        (new GenerateFeedsCron($framework))();
    }
}
