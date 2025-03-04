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
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

/**
 * @internal
 */
class RememberMeMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        return $schemaManager->tablesExist('tl_remember_me') && !$schemaManager->tablesExist('rememberme_token');
    }

    public function run(): MigrationResult
    {
        $table = new Table('rememberme_token');
        $table->addColumn('series', Types::STRING, ['length' => 88]);
        $table->addColumn('value', Types::STRING, ['length' => 88]);
        $table->addColumn('lastUsed', Types::DATETIME_IMMUTABLE);
        $table->addColumn('class', Types::STRING, ['length' => 100]);
        $table->addColumn('username', Types::STRING, ['length' => 200]);
        $table->setPrimaryKey(['series']);

        $params = $this->connection->getParams()['defaultTableOptions'] ?? [];

        if (isset($params['charset'])) {
            $table->addOption('charset', $params['charset']);
        }

        if (isset($params['engine'])) {
            $table->addOption('engine', $params['engine']);
        }

        if (isset($params['collate'])) {
            $table->addOption('collate', $params['collate']);
        }

        $schemaManager = $this->connection->createSchemaManager();
        $schemaManager->createTable($table);

        // If tl_remember_me.userIdentifier exists, the database is from Contao 5 and the
        // existing tokens can be migrated. Otherwise, it is a Contao 4 database and the
        // tokens would not work anyway and therefore do not need to be migrated.
        if (isset($schemaManager->listTableColumns('tl_remember_me')['useridentifier'])) {
            $this->connection->executeStatement(<<<'SQL'
                INSERT INTO rememberme_token (
                    SELECT
                        TRIM(TRAILING CHAR(0) FROM CAST(series AS char)),
                        TRIM(TRAILING CHAR(0) FROM CAST(value AS char)),
                        lastUsed,
                        class,
                        userIdentifier
                    FROM tl_remember_me
                    WHERE userIdentifier != ''
                )
                SQL,
            );
        }

        $this->connection->executeStatement('DROP TABLE tl_remember_me');

        return $this->createResult(true);
    }
}
