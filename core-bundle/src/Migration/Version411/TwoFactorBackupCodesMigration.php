<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version411;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class TwoFactorBackupCodesMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user', 'tl_member'])) {
            return false;
        }

        $userColumns = $schemaManager->listTableColumns('tl_user');
        $memberColumns = $schemaManager->listTableColumns('tl_member');

        if (!isset($userColumns['backupcodes'], $memberColumns['backupcodes'])) {
            return false;
        }

        $users = $this->getAffectedRowsForTable('tl_user');
        $members = $this->getAffectedRowsForTable('tl_member');

        return \count($users) || \count($members);
    }

    public function run(): MigrationResult
    {
        $this->updateBackupCodes('tl_user');
        $this->updateBackupCodes('tl_member');

        return $this->createResult(true);
    }

    private function updateBackupCodes(string $table): void
    {
        $rows = $this->getAffectedRowsForTable($table);

        foreach ($rows as $row) {
            $backupCodes = json_decode($row['backupCodes'], true);

            if (!\is_array($backupCodes)) {
                continue;
            }

            foreach ($backupCodes as $key => $backupCode) {
                $backupCodes[$key] = password_hash($backupCode, PASSWORD_DEFAULT);
            }

            $this->connection->executeStatement(
                "UPDATE $table SET backupCodes = :backupCodes WHERE id = :id",
                [
                    'backupCodes' => json_encode($backupCodes),
                    'id' => $row['id'],
                ]
            );
        }
    }

    private function getAffectedRowsForTable(string $table): array
    {
        return $this->connection->fetchAllAssociative("
            SELECT
                id, backupCodes
            FROM
                $table
            WHERE
                backupCodes IS NOT NULL AND backupCodes REGEXP '[a-f0-9]{6}-[a-f0-9]{6}'
        ");
    }
}
