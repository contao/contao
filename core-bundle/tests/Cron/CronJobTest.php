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

use Contao\CoreBundle\Cron\Cron;
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

    public function testUnexpectedReturnValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid return value from "Contao\CoreBundle\Fixtures\Cron\TestCronJob::wrongReturnValue": expected null or PromiseInterface, got int');

        $cronJob = new CronJob(new TestCronJob(), '@daily', 'wrongReturnValue');
        $cronJob(Cron::SCOPE_CLI);
    }
}
