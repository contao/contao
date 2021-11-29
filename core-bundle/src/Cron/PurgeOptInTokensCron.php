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
use Doctrine\DBAL\Connection;
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
     * @var Connection
     */
    private $database;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(OptIn $optIn, Connection $database, LoggerInterface $logger = null)
    {
        $this->optIn = $optIn;
        $this->logger = $logger;
        $this->database = $database;
    }

    public function __invoke(): void
    {
        $count = (int) $this->database->fetchOne('SELECT COUNT(*) FROM tl_opt_in');

        $this->optIn->purgeTokens();

        $count -= (int) $this->database->fetchOne('SELECT COUNT(*) FROM tl_opt_in');

        if ($count > 0 && null !== $this->logger) {
            $this->logger->info(sprintf('Purged %s expired double opt-in tokens', $count), ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]);
        }
    }
}
