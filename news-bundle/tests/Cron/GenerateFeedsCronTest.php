<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\EventListener;

use Contao\News;
use Contao\NewsBundle\Cron\GenerateFeedsCron;
use Contao\TestCase\ContaoTestCase;

class GenerateFeedsCronTest extends ContaoTestCase
{
    public function testExecutesGenerateFeeds(): void
    {
        $calendarUtil = $this->createMock(News::class);
        $calendarUtil
            ->expects($this->exactly(1))
            ->method('generateFeeds')
        ;

        $framework = $this->mockContaoFramework([], [News::class => $calendarUtil]);

        (new GenerateFeedsCron($framework))();
    }
}
