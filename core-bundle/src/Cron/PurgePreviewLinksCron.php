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

use Contao\CoreBundle\ServiceAnnotation\CronJob;
use Doctrine\DBAL\Connection;

/**
 * Deletes preview links that are older than 31 days, since the maximum expiration is 30 days.
 * We don't purge right after expiration date since days can be changed to increase the lifetime.
 *
 * @CronJob("daily")
 *
 * @internal
 */
class PurgePreviewLinksCron
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function __invoke(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM tl_preview_link WHERE createdAt<=UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 31 DAY))'
        );
    }
}
