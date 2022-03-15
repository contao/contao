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

use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class PurgeExpiredDataCron
{
    private ContaoFramework $framework;
    private Connection $connection;

    public function __construct(ContaoFramework $framework, Connection $connection)
    {
        $this->framework = $framework;
        $this->connection = $connection;
    }

    /**
     * @CronJob("hourly")
     */
    public function onHourly(): void
    {
        $this->framework->initialize();

        $config = $this->framework->getAdapter(Config::class);

        $this->cleanTable('tl_undo', (int) $config->get('undoPeriod'));
        $this->cleanTable('tl_log', (int) $config->get('logPeriod'));
        $this->cleanTable('tl_version', (int) $config->get('versionPeriod'));
    }

    private function cleanTable(string $table, int $period): void
    {
        if ($period <= 0) {
            return;
        }

        $this->connection->executeStatement(
            "DELETE FROM $table WHERE tstamp < :tstamp",
            ['tstamp' => time() - $period],
            ['tstamp' => Types::INTEGER],
        );
    }
}
