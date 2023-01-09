<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Fixtures\Cron;

use Contao\CoreBundle\Exception\CronExecutionSkippedException;

class TestCronJob
{
    public function onMinutely(): void
    {
    }

    public function onHourly(): void
    {
    }

    public function onDaily(): void
    {
    }

    public function onWeekly(): void
    {
    }

    public function onMonthly(): void
    {
    }

    public function customMethod(): void
    {
    }

    public function skippingMethod(): void
    {
        throw new CronExecutionSkippedException();
    }
}
