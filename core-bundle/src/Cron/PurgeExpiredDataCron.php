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
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

class PurgeExpiredDataCron
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly ClockInterface $clock = new NativeClock(),
    ) {
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
            ['tstamp' => $this->clock->now()->getTimestamp() - $period],
            ['tstamp' => Types::INTEGER],
        );
    }
}
