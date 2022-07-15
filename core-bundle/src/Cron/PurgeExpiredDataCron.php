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
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class PurgeExpiredDataCron
{
    public function __construct(private ContaoFramework $framework, private Connection $connection)
    {
    }

    #[AsCronJob('hourly')]
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
