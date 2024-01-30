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
        $this->connection->executeStatement(
            <<<'SQL'
                CREATE TABLE `rememberme_token` (
                    `series`   varchar(88)  UNIQUE PRIMARY KEY NOT NULL,
                    `value`    varchar(88)  NOT NULL,
                    `lastUsed` datetime     NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                    `class`    varchar(100) NOT NULL,
                    `username` varchar(200) NOT NULL
                );
                SQL,
        );

        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO rememberme_token (
                    SELECT
                        TRIM(TRAILING CHAR(0x00) FROM series),
                        TRIM(TRAILING CHAR(0x00) FROM value),
                        lastUsed,
                        class,
                        userIdentifier
                    FROM tl_remember_me
                )
                SQL,
        );

        $this->connection->executeStatement('DROP TABLE tl_remember_me');

        return $this->createResult(true);
    }
}
