<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Cron;

use Contao\CoreBundle\Cron\PurgeJobsCron;
use Contao\CoreBundle\Job\Jobs;
use PHPUnit\Framework\TestCase;

class PurgeJobsCronTest extends TestCase
{
    public function testInvoke(): void
    {
        $jobs = $this->createMock(Jobs::class);
        $jobs
            ->expects($this->once())
            ->method('prune')
            ->with(86400)
        ;

        $cron = new PurgeJobsCron($jobs);
        $cron();
    }
}
