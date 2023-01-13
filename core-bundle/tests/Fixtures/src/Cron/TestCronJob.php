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
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;

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

    public function asyncMethod(): PromiseInterface|null
    {
        return $promise = new Promise(static function () use (&$promise): void { $promise->resolve('promise'); });
    }

    public function skippingMethod(): never
    {
        throw new CronExecutionSkippedException();
    }
}
