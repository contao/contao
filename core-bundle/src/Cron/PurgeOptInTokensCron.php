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

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\OptIn\OptIn;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Psr\Log\LoggerInterface;

/**
 * @CronJob("hourly")
 */
class PurgeOptInTokensCron
{
    /**
     * @var OptIn
     */
    private $optIn;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(OptIn $optIn, LoggerInterface $logger = null)
    {
        $this->optIn = $optIn;
        $this->logger = $logger;
    }

    public function __invoke(): void
    {
        $this->optIn->purgeTokens();

        if (null !== $this->logger) {
            $this->logger->info('Purged the expired double opt-in tokens', ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]);
        }
    }
}
