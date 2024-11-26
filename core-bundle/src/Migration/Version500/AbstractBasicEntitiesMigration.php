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
use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * This class can be used as a base class for migrations in extensions for
 * migrating basic entities in text fields to real entities.
 *
 * @see BasicEntitiesMigration for an example how to use this base class.
 */
abstract class AbstractBasicEntitiesMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        // This migration is very intrusive thus we try to run it only if the database
        // schema was not yet updated to Contao 5
        if (
            !$schemaManager->tablesExist(['tl_article'])
            || !isset($schemaManager->listTableColumns('tl_article')['keywords'])
            || !$schemaManager->tablesExist(['tl_theme'])
            || !isset($schemaManager->listTableColumns('tl_theme')['vars'])
        ) {
            return false;
        }

        foreach ($this->getDatabaseColumns() as [$table, $column]) {
            $table = strtolower($table);
            $column = strtolower($column);

            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)[$column])) {
                continue;
            }

            $test = $this->connection->fetchOne("
                SELECT TRUE
                FROM $table
                WHERE CAST(`$column` AS BINARY) REGEXP '\\\\[(&|&amp;|lt|gt|nbsp|-)\\\\]'
                LIMIT 1
            ");

            if (false !== $test) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($this->getDatabaseColumns() as [$table, $column]) {
            $table = strtolower($table);
            $column = strtolower($column);

            if (!$schemaManager->tablesExist([$table]) || !isset($schemaManager->listTableColumns($table)[$column])) {
                continue;
            }

            $values = $this->connection->fetchAllKeyValue("
                SELECT
                    id,
                    `$column`
                FROM $table
                WHERE CAST(`$column` AS BINARY) REGEXP '\\\\[(&|&amp;|lt|gt|nbsp|-)\\\\]'
            ");

            foreach ($values as $id => $value) {
                $value = StringUtil::deserialize($value);

                if (\is_array($value)) {
                    array_walk_recursive($value, static fn (&$v) => $v = StringUtil::restoreBasicEntities($v));
                    $value = serialize($value);
                } else {
                    $value = StringUtil::restoreBasicEntities($value);
                }

                $this->connection->update($table, [$column => $value], ['id' => (int) $id]);
            }
        }

        return $this->createResult(true);
    }

    /**
     * Returns an array of arrays with the first element being the database table name
     * and the second being the column name.
     *
     * For example:
     *
     *     return [
     *         ['tl_news', 'title'],
     *         ['tl_news', 'description'],
     *     ];
     *
     * @return list<array{0:string, 1:string}>
     */
    abstract protected function getDatabaseColumns(): array;
}
