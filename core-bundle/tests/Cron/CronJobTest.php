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
}
