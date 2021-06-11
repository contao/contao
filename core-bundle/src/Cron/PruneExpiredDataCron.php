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

class PruneExpiredDataCron
{
    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @var Connection
     */
    private $connection;

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

        $undoPeriod = (int) $this->framework->getAdapter(Config::class)->get('undoPeriod');
        $logPeriod = (int) $this->framework->getAdapter(Config::class)->get('logPeriod');
        $versionPeriod = (int) $this->framework->getAdapter(Config::class)->get('versionPeriod');

        $this->cleanTable('tl_undo', $undoPeriod);
        $this->cleanTable('tl_log', $logPeriod);
        $this->cleanTable('tl_version', $versionPeriod);
    }

    private function cleanTable(string $table, int $period): void
    {
        if ($period <= 0) {
            return;
        }

        $stmt = $this->connection->prepare(sprintf('DELETE FROM %s WHERE tstamp<:tstamp', $table));
        $stmt->executeStatement(['tstamp' => time() - $period]);
    }
}
