<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Job\Jobs;

#[AsCronJob('daily')]
class PurgeJobsCron
{
    public function __construct(private readonly Jobs $jobs)
    {
    }

    public function __invoke(): void
    {
        $this->jobs->prune(86400); // 1 day;
    }
}
