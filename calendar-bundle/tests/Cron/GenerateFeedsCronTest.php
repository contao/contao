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
use Contao\System;
use Contao\TestCase\ContaoTestCase;

class GenerateFeedsCronTest extends ContaoTestCase
{
    public function testExecutesGenerateFeeds(): void
    {
        $calendar = $this->createMock(Calendar::class);
        $calendar
            ->expects($this->once())
            ->method('generateFeeds')
        ;

        $system = $this->mockAdapter(['loadLanguageFile']);
        $system
            ->expects($this->once())
            ->method('loadLanguageFile')
            ->with('default')
        ;

        $framework = $this->mockContaoFramework([System::class => $system], [Calendar::class => $calendar]);

        (new GenerateFeedsCron($framework))();
    }
}
