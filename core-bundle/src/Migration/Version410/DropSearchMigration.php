<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version410;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class DropSearchMigration extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (
            !$schemaManager->tablesExist('tl_search_index')
            || $schemaManager->tablesExist('tl_search_term')
        ) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_search_index');

        if (isset($columns['termid'])) {
            return false;
        }

        return true;
    }

    public function run(): MigrationResult
    {
        $this->connection->query('DROP TABLE IF EXISTS tl_search_index');
        $this->connection->query('DROP TABLE IF EXISTS tl_search');

        return $this->createResult(true);
    }
}
