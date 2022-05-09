<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Cron;

use Contao\CoreBundle\OptIn\OptIn;
use Psr\Log\LoggerInterface;

class PurgeOptInTokensCron
{
    public function __construct(private OptIn $optIn, private LoggerInterface|null $logger)
    {
    }

    public function __invoke(): void
    {
        $this->optIn->purgeTokens();

        if (null !== $this->logger) {
            $this->logger->info('Purged the expired double opt-in tokens');
        }
    }
}
