<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version500;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\BooleanType;

/**
 * Removes '0' from tl_page.accesskey and tl_form_field.accesskey as this was accidentally introduced (see #5586).
 */
class AccesskeyMigration extends AbstractMigration
{
    private static array $affectedTables = ['tl_page', 'tl_form_field'];

    public function __construct(private Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        foreach (self::$affectedTables as $table) {
            if ($this->needsMigration($table)) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        foreach (self::$affectedTables as $table) {
            if ($this->needsMigration($table)) {
                $this->connection->executeQuery("ALTER TABLE $table CHANGE accesskey accesskey CHAR(1) DEFAULT '' NOT NULL");
                $this->connection->executeQuery("UPDATE $table SET accesskey = '' WHERE accesskey = '0'");
            }
        }

        return $this->createResult(true);
    }

    private function needsMigration(string $table): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist($table)) {
            return false;
        }

        $columns = $schemaManager->listTableColumns($table);

        if (!isset($columns['accesskey'])) {
            return false;
        }

        return $columns['accesskey']->getType() instanceof BooleanType;
    }
}
