<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version413;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * @internal
 */
class PageSubpagesLayoutMigration extends AbstractMigration
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

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return false;
        }

        $pageColumns = $schemaManager->listTableColumns('tl_page');

        return !isset($pageColumns['subpageslayout']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('
            ALTER TABLE
                tl_page
            ADD
                subpagesLayout int(10) unsigned NOT NULL default 0
        ');

        $this->connection->executeStatement("
            UPDATE
                tl_page
            SET
                subpagesLayout = layout
            WHERE
                includeLayout = '1'
        ");

        return $this->createResult(true);
    }
}
