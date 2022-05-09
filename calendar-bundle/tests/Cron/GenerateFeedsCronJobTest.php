<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CalendarBundle\Tests\EventListener;

use Contao\Calendar;
use Contao\CalendarBundle\Cron\GenerateFeedsCronJob;
use Contao\TestCase\ContaoTestCase;

class GenerateFeedsCronJobTest extends ContaoTestCase
{
    public function testExecutesGenerateFeeds(): void
    {
        $calendarUtil = $this->createMock(Calendar::class);
        $calendarUtil
            ->expects($this->exactly(1))
            ->method('generateFeeds')
        ;

        $framework = $this->mockContaoFramework([], [Calendar::class => $calendarUtil]);

        (new GenerateFeedsCronJob($framework))();
    }
}
