<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\InstallationBundle\Database;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class Version350Update extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Contao 3.5.0 Update';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_member'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_member');

        return isset($columns['username']) && true === $columns['username']->getNotnull();
    }

    public function run(): MigrationResult
    {
        $this->connection->query('
            ALTER TABLE
                tl_member
            CHANGE
                username username varchar(64) COLLATE utf8_bin NULL
        ');

        $this->connection->query("
            UPDATE
                tl_member
            SET
                username = NULL
            WHERE
                username = ''
        ");

        $this->connection->query('
            ALTER TABLE
                tl_member
            DROP INDEX
                username,
            ADD UNIQUE KEY
                username (username)
        ');

        return $this->createResult(true);
    }
}
