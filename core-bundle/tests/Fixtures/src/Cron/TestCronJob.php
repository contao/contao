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

use Contao\CoreBundle\Cron\ProcessCollection;
use Symfony\Component\Process\Process;

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

    public function processMethod(): ProcessCollection
    {
        return ProcessCollection::fromSingle(new Process([]), 'process-1');
    }

    public function processesMethod(): ProcessCollection
    {
        $collection = new ProcessCollection();
        $collection->add(new Process([]), 'process-1');
        $collection->add(new Process([]), 'process-2');

        return $collection;
    }
}
