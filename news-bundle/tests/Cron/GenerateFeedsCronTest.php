<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsBundle\Tests\Cron;

use Contao\News;
use Contao\NewsBundle\Cron\GenerateFeedsCron;
use Contao\TestCase\ContaoTestCase;

class GenerateFeedsCronTest extends ContaoTestCase
{
    public function testExecutesGenerateFeeds(): void
    {
        $newsUtil = $this->createMock(News::class);
        $newsUtil
            ->expects($this->once())
            ->method('generateFeeds')
        ;

        $framework = $this->mockContaoFramework([], [News::class => $newsUtil]);

        (new GenerateFeedsCron($framework))();
    }
}
