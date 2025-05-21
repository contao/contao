<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version506;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 *  Converts the value of the old field 'noSearch' to the new field 'searchIndexer'.
 *  The new field is introduced to be able to always or never index a page
 *  despite the robots tag setting (the robots tag setting is used as default).
 */
class SearchIndexerSettingsMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_page'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_page');

        if (!isset($columns['nosearch']) || isset($columns['searchindexer'])) {
            return false;
        }

        return true;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('
            ALTER TABLE tl_page
            ADD searchIndexer varchar(32) NOT NULL default ""
        ');

        // Migrate the setting from the old 'noSearch' field
        $this->connection->executeStatement('
            UPDATE tl_page
            SET searchIndexer = "never_index"
            WHERE noSearch = 1
        ');

        return $this->createResult(true);
    }
}
