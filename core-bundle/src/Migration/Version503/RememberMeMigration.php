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
        $this->connection->executeStatement(<<<'SQL'
            CREATE TABLE `rememberme_token` (
                `series`   varchar(88)  UNIQUE PRIMARY KEY NOT NULL,
                `value`    varchar(88)  NOT NULL,
                `lastUsed` datetime     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                `class`    varchar(100) NOT NULL,
                `username` varchar(200) NOT NULL
            );
            SQL,
        );

        $schemaManager = $this->connection->createSchemaManager();

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
