<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version503;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class RenameTablesMigration extends AbstractMigration
{
    private static array $tables = [
        'tl_crawl_queue' => 'crawl_queue',
        'tl_cron_job' => 'cron_job',
        'tl_remember_me' => 'rememberme_token',
        'tl_trusted_device' => 'trusted_device',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        return $schemaManager->tablesExist(array_keys(self::$tables));
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach (self::$tables as $old => $new) {
            if (!$schemaManager->tablesExist([$old])) {
                continue;
            }

            $this->connection->executeStatement("RENAME TABLE $old TO $new");
        }

        return $this->createResult(true);
    }
}
