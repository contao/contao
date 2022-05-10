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

use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Psr\Log\LoggerInterface;

/**
 * @CronJob("daily")
 */
class PurgeOptInTokensCron
{
    public function __construct(private OptIn $optIn, private LoggerInterface|null $logger)
    {
    }

    public function __invoke(): void
    {
        $this->optIn->purgeTokens();

        $this->logger?->info('Purged the expired double opt-in tokens');
    }
}
