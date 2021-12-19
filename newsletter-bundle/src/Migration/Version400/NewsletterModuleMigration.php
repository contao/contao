<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Migration\Version400;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class NewsletterModuleMigration extends AbstractMigration
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_module'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_module');

        if (!isset($columns['type'])) {
            return false;
        }

        return $this->connection->fetchOne("SELECT COUNT(*) FROM tl_module WHERE type='nl_list' OR type='nl_reader'") > 0;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement("
            UPDATE
                tl_module
            SET
                type = 'newsletterlist'
            WHERE
                type = 'nl_list'
        ");

        $this->connection->executeStatement("
            UPDATE
                tl_module
            SET
                type = 'newsletterreader'
            WHERE
                type = 'nl_reader'
        ");

        return $this->createResult(true);
    }
}
