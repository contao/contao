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

use Contao\CoreBundle\Cron\CronJob;
use Contao\CoreBundle\Fixtures\Cron\TestCronJob;
use Contao\CoreBundle\Tests\TestCase;

class CronJobTest extends TestCase
{
    public function testThrowsExceptionIfNoMethodIsGivenAndServiceIsNotInvokable(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new CronJob(new TestCronJob(), '@hourly');
    }

    public function testAsyncCronJob(): void
    {
        $sync = new CronJob(new TestCronJob(), '@hourly', 'customMethod');
        $async1 = new CronJob(new TestCronJob(), '@hourly', 'processMethod');
        $async2 = new CronJob(new TestCronJob(), '@hourly', 'processesMethod');

        $this->assertFalse($sync->isAsync());
        $this->assertTrue($async1->isAsync());
        $this->assertTrue($async2->isAsync());
    }
}
