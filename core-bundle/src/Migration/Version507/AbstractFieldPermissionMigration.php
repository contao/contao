<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version507;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

abstract class AbstractFieldPermissionMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_user', 'tl_user_group'])) {
            return false;
        }

        $permissionFields = array_map(strtolower(...), array_keys($this->getMapping()));

        foreach (['tl_user', 'tl_user_group'] as $table) {
            $tableColumns = array_keys($schemaManager->listTableColumns($table));

            if (!\in_array('cud', $tableColumns, true)) {
                continue;
            }

            if ([] !== array_intersect($permissionFields, $tableColumns)) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();
        $mapping = $this->getMapping();

        $this->migrateTable($schemaManager, $mapping, 'tl_user_group');
        $this->migrateTable($schemaManager, $mapping, 'tl_user', "WHERE inherit IN ('extend', 'custom') AND admin = false");

        return $this->createResult(true);
    }

    abstract protected function getMapping(): array;

    /**
     * @param AbstractSchemaManager<AbstractMySQLPlatform> $schemaManager
     */
    private function migrateTable(AbstractSchemaManager $schemaManager, array $mapping, string $table, string $where = ''): void
    {
        $columns = array_keys($schemaManager->listTableColumns($table));

        foreach ($mapping as $field => $operations) {
            if (\array_key_exists(strtolower($field), $columns)) {
                unset($mapping[$field]);
            }
        }

        if ([] === $mapping) {
            return;
        }

        $records = $this->connection->fetchAllAssociative("SELECT * FROM $table $where");

        foreach ($records as $row) {
            $cud = StringUtil::deserialize($row['cud'], true);
            $unset = [];

            foreach ($mapping as $field => $operations) {
                $value = StringUtil::deserialize($row[$field], true);

                foreach ($operations as $key => $operation) {
                    if (!\in_array($key, $value, true)) {
                        $unset[] = $operation;
                    }
                }
            }

            if ([] !== $unset) {
                $cud = array_diff($cud, $unset);
                $this->connection->update($table, ['cud' => serialize($cud)], ['id' => $row['id']]);
            }
        }

        $this->connection->executeStatement("ALTER TABLE $table DROP COLUMN ".implode(', DROP COLUMN ', array_keys($mapping)));
    }
}
