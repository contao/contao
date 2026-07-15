<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Migration\Version600;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

abstract class AbstractColumnToVirtualMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();
        $mapping = $this->getMapping();

        foreach ($mapping as $table => $fields) {
            // Ignore tables that do not exist
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $tableColumns = array_keys($schemaManager->listTableColumns($table));

            // If there is at least one column that should be a virtual field, run the migration
            if (array_any($fields, static fn ($targetColumn, $field) => \in_array(strtolower($field), $tableColumns, true))) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();
        $mapping = $this->getMapping();

        foreach ($mapping as $table => $fields) {
            // Ignore tables that do not exist
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $tableColumns = array_keys($schemaManager->listTableColumns($table));

            foreach ($fields as $field => $targetColumn) {
                if (!\in_array(strtolower($field), $tableColumns, true)) {
                    continue;
                }

                $rows = $this->connection->fetchAllAssociative("SELECT id, `$field`, `$targetColumn` FROM `$table`");

                foreach ($rows as $row) {
                    if (null === $row[$targetColumn]) {
                        $jsonData = [];
                    } else {
                        $jsonData = json_decode($row[$targetColumn], true, flags: JSON_THROW_ON_ERROR);
                    }

                    if (isset($jsonData[$field])) {
                        continue;
                    }

                    $jsonData[$field] = StringUtil::ensureStringUuids($row[$field]);

                    $this->connection->update($table, [$targetColumn => json_encode($jsonData, flags: JSON_THROW_ON_ERROR)], ['id' => $row['id']]);
                }

                $this->connection->executeQuery("ALTER TABLE `$table` DROP COLUMN `$field`");
            }
        }

        return $this->createResult(true);
    }

    /**
     * Returns a mapping of tables and fields that should be converted to virtual fields.
     *
     * Example:
     *   [
     *     'tl_content' => [
     *       'playerStart' => 'jsonData',
     *       'playerStop' => 'jsonData',
     *     ],
     *   ]
     *
     * @return array<string, array<string, string>>
     */
    abstract protected function getMapping(): array;
}
