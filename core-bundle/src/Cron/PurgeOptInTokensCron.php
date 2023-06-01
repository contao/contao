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
use Contao\CoreBundle\OptIn\OptIn;
use Psr\Log\LoggerInterface;

#[AsCronJob('daily')]
class PurgeOptInTokensCron
{
    public function __construct(
        private readonly OptIn $optIn,
        private readonly LoggerInterface|null $logger,
    ) {
    }

    public function __invoke(): void
    {
        $this->optIn->purgeTokens();

        $this->logger?->info('Purged the expired double opt-in tokens');
    }
}
