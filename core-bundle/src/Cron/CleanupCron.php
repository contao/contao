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
use Doctrine\DBAL\Connection;

class CleanupCron
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
     * @\Contao\CoreBundle\ServiceAnnotation\CronJob("daily")
     */
    public function onDaily(): void
    {
        $this->framework->initialize();

        $undoPeriod = (int) $this->framework->getAdapter(Config::class)->get('undoPeriod');
        $logPeriod = (int) $this->framework->getAdapter(Config::class)->get('logPeriod');

        // Clean up old tl_undo and tl_log entries
        if ($undoPeriod > 0) {
            $stmt = $this->connection->prepare('DELETE FROM tl_undo WHERE tstamp<:tstamp');
            $stmt->executeStatement(['tstamp' => time() - $undoPeriod]);
        }

        if ($logPeriod > 0) {
            $stmt = $this->connection->prepare('DELETE FROM tl_log WHERE tstamp<:tstamp');
            $stmt->executeStatement(['tstamp' => time() - $logPeriod]);
        }
    }
}
