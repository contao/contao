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
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

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

            $tableDefinition = $schemaManager->introspectTableByUnquotedName($table);

            // If there is at least one column that should be a virtual field, run the migration
            if (array_any($fields, static fn ($targetColumn, $field) => $tableDefinition->hasColumn($field))) {
                return true;
            }
        }

        return false;
    }

    public function run(): MigrationResult
    {
        $platform = $this->connection->getDatabasePlatform();
        $schemaManager = $this->connection->createSchemaManager();
        $mapping = $this->getMapping();

        foreach ($mapping as $table => $fields) {
            // Ignore tables that do not exist
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $tableQuoted = $platform->quoteSingleIdentifier($table);
            $tableDefinition = $schemaManager->introspectTableByUnquotedName($table);

            foreach ($fields as $field => $targetColumn) {
                if (!$tableDefinition->hasColumn($field)) {
                    continue;
                }

                $fieldQuoted = $platform->quoteSingleIdentifier($field);
                $targetColumnQuoted = $platform->quoteSingleIdentifier($targetColumn);

                // Add the target column if it doesn‘t exist
                if (!$tableDefinition->hasColumn($targetColumn)) {
                    $column = new Column(
                        $targetColumn,
                        Type::getType(Types::JSON),
                        [
                            'length' => AbstractMySQLPlatform::LENGTH_LIMIT_MEDIUMTEXT,
                            'notnull' => false,
                        ],
                    );

                    $this->connection->executeStatement(\sprintf(
                        'ALTER TABLE %s ADD %s',
                        $tableQuoted,
                        $platform->getColumnDeclarationSQL($targetColumnQuoted, $column->toArray()),
                    ));

                    // Add the target column to the table definition so the `hasColumn()` method
                    // returns `true` in the next iteration.
                    $tableDefinition->addColumn($targetColumn, Types::JSON);
                }

                $rows = $this->connection->fetchAllAssociative(\sprintf('SELECT id, %s, %s FROM %s', $fieldQuoted, $targetColumnQuoted, $tableQuoted));

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

                $this->connection->executeQuery(\sprintf('ALTER TABLE %s DROP COLUMN %s', $tableQuoted, $fieldQuoted));
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
